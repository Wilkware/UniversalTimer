<?php

declare(strict_types=1);

/** Generell funktions */
require_once __DIR__ . '/../libs/_traits.php';

/** Namespaced traits */
use Wilkware\UniversalTimer\DebugHelper;
use Wilkware\UniversalTimer\EventHelper;
use Wilkware\UniversalTimer\VariableHelper;

/**
 * CLASS Universal Timer
 */
class UniversalTimer extends IPSModuleStrict
{
    // -------------------------------------------------------------------------
    // Traits
    // -------------------------------------------------------------------------

    use DebugHelper;
    use EventHelper;
    use VariableHelper;

    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    /** @var string Active status color */
    private const STATUS_ACTIVE = '#C0FFC0';

    /** @var string Active status color */
    private const STATUS_INACTIVE = '#FFC0C0';

    /** @var string No time constants */
    private const TIME_NONE = '--:--:--';

    /** @var string Time reset constants */
    private const TIME_RESET = '{"hour": -1, "minute": -1, "second": -1 }';

    /** @var string Event constants */
    private const EVENT_OFF = 'Off';

    /** @var string Event separator constants */
    private const EVENT_SEPERATOR = 'None';

    /** @var array<string,string> Event values mapping */
    private const EVENT_VALUES = [
        'AstronomicTwilightStart' => 'ATS',
        'NauticTwilightStart'     => 'NTS',
        'CivilTwilightStart'      => 'CTS',
        'Sunrise'                 => 'SR',
        'Sunset'                  => 'SS',
        'CivilTwilightEnd'        => 'CTE',
        'NauticTwilightEnd'       => 'NTE',
        'AstronomicTwilightEnd'   => 'ATE',
        'ExternalTrigger'         => 'ET',
    ];

    // -------------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------------

    /**
     * In contrast to Construct, this function is called only once when creating the instance and starting IP-Symcon.
     * Therefore, status variables and module properties which the module requires permanently should be created here.
     *
     * @return void
     */
    public function Create(): void
    {
        //Never delete this line!
        parent::Create();
        // Instance
        $this->RegisterPropertyBoolean('InstanceActive', true);
        // Time control
        $this->RegisterPropertyString('Timetable', '[]');
        // Device
        $this->RegisterPropertyString('DeviceVariables', '[]');
        $this->RegisterPropertyInteger('DeviceScript', 0);
        // Settings
        $this->RegisterPropertyInteger('SettingsTrigger', 0);
        $this->RegisterPropertyBoolean('SettingsSwitch', false);
    }

    /**
     * This function is called when deleting the instance during operation and when updating via "Module Control".
     * The function is not called when exiting IP-Symcon.
     *
     * @return void
     */
    public function Destroy(): void
    {
        //Never delete this line!
        parent::Destroy();
    }

    /**
     * The content can be overwritten in order to transfer a self-created configuration page.
     * This way, content can be generated dynamically.
     * In this case, the "form.json" on the file system is completely ignored.
     *
     * @return string Content of the configuration page.
     */
    public function GetConfigurationForm(): string
    {
        // Get Form
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        // Extract Version
        $ins = IPS_GetInstance($this->InstanceID);
        $mod = IPS_GetModule($ins['ModuleInfo']['ModuleID']);
        $lib = IPS_GetLibrary($mod['LibraryID']);
        $form['actions'][0]['items'][2]['caption'] = sprintf('v%s.%d', $lib['Version'], $lib['Build']);

        // Timetable List
        $value = $this->ReadPropertyString('Timetable');
        $list = json_decode($value, true);
        foreach ($list as &$line) {
            $line['_'] = '≡';
            $line['editable'] = false;
            $line['rowColor'] = ($line['status'] == 1) ? self::STATUS_ACTIVE : self::STATUS_INACTIVE;
        }
        $this->LogDebug(__FUNCTION__, json_encode($list));
        $form['elements'][2]['items'][0]['values'] = $list;
        // Reset time fields
        $form['elements'][2]['items'][8]['items'][1]['value'] = self::TIME_RESET;
        $form['elements'][2]['items'][10]['items'][1]['value'] = self::TIME_RESET;
        $form['elements'][2]['items'][10]['items'][4]['value'] = self::TIME_RESET;

        // Debug output
        $this->LogDebug(__FUNCTION__, $form);
        return json_encode($form);
    }

    /**
     * Is executed when "Apply" is pressed on the configuration page and immediately after the instance has been created.
     *
     * @return void
     */
    public function ApplyChanges(): void
    {
        //Never delete this line!
        parent::ApplyChanges();

        $list = $this->ReadPropertyString('Timetable');
        $this->LogDebug(__FUNCTION__, $list);

        // Aditionally Switch
        $switch = $this->ReadPropertyBoolean('SettingsSwitch');
        $this->MaintainVariable('switch_proxy', $this->Translate('Switch'), VARIABLETYPE_BOOLEAN, '~Switch', 0, $switch);
        if ($switch) {
            $this->EnableAction('switch_proxy');
        }
    }

    /**
     * Is called when, for example, a button is clicked in the visualization.
     *
     * @param string $ident Ident of the variable
     * @param mixed $value The value to be set
     * @return void
     */
    public function RequestAction(string $ident, mixed $value): void
    {
        // Debug output
        $this->LogDebug(__FUNCTION__, $ident . ' => ' . $value);
        switch ($ident) {
            case 'OnAdd':
                $this->OnAddList($value);
                break;
            case 'OnCopy':
                $this->OnCopyList($value);
                break;
            case 'OnEvent':
                $this->OnCheckEvent($value);
                break;
            case 'OnReset':
                $this->OnResetTime($value);
                break;
            case 'OnSelect':
                $this->OnSelectList($value);
                break;
            case 'OnSort':
                $this->OnSortList($value);
                break;
            case 'OnUpdate':
                $this->OnUpdateList($value);
                break;
            case 'switch_proxy':
                if ($this->SwitchDevice($value)) {
                    $this->SetValueBoolean($ident, $value);
                }
                break;
            default:
                break;
        }
        //return true;
    }

    /**
     * Called from configuration form if an entry is added to the timetable list.
     *
     * @param string $value json encoded list.
     * @return void
     */
    private function OnAddList(string $value): void
    {
        $list = json_decode($value, true);
        // fix inner list as array
        foreach ($list as &$line) {
            $line['schedule'] = json_decode($line['schedule'], true);
        }
        // calculate next line
        $id = empty($list) ? 1 : count($list) + 1;
        // prepare data
        $data = [
            '_'         => '≡',
            'id'        => $id,
            'status'    => 2,
            'rule'      => 0,
            'action'    => true,
            'time'      => self::TIME_NONE,
            'monday'    => true, 'tuesday' => true, 'wednesday' => true, 'thursday' => true, 'friday' => true, 'saturday' => true, 'sunday' => true,
            'schedule'  => ['earliest' => self::TIME_NONE, 'event' => 'Off', 'offset' => '0', 'latest' => self::TIME_NONE],
            'conditions'=> false,
            'condition' => '[]',
            'editable'  => false,
            'rowColor'  => self::STATUS_INACTIVE,
        ];
        // add data
        $list[] = $data;
        $this->UpdateFormField('Timetable', 'values', json_encode($list));
    }

    /**
     * Duplicate a entry of the timetable list.
     *
     * @param string $value json encoded list plus index.
     * @return void
     */
    private function OnCopyList(string $value): void
    {
        $this->LogDebug(__FUNCTION__, $value);
        $list = json_decode($value, true);

        // how many lines in the list?
        $last = count($list);
        // last line has copy index id
        $copy = $list[$last - 1];
        // copy line to last
        for ($index = 0; $index < $last; $index++) {
            if ($list[$index]['id'] == $copy) {
                $list[$last - 1] = $list[$index];
                break;
            }
        }
        // sort
        $id = 1;
        foreach ($list as &$line) {
            $this->LogDebug(__FUNCTION__, $line['id'] . ' => ' . $id);
            // only if nesseccary
            if ($line['id'] != $id) {
                $line['id'] = $id;
                $sort = true;
            }
            // restore inline arrays
            $line['schedule'] = json_decode($line['schedule'], true);
            // increment
            $id++;
        }
        $this->UpdateFormField('Timetable', 'values', json_encode($list));
    }

    /**
     * User has select an new event trigger.
     *
     * @param string $value event.
     * @return void
     */
    private function OnCheckEvent(string $value): void
    {
        if ($value == self::EVENT_SEPERATOR) {
            $this->UpdateFormField('SelectedEvent', 'value', self::EVENT_OFF);
        }
    }

    /**
     * Reset selected timevalue.
     *
     * @param string $value name of the form field.
     * @return void
     */
    private function OnResetTime(string $value): void
    {
        $this->LogDebug(__FUNCTION__, $value);
        $this->UpdateFormField($value, 'value', self::TIME_RESET);
    }

    /**
     * Re-sort the timetable list.
     *
     * @param string $value json encoded list.
     * @return void
     */
    private function OnSortList(string $value): void
    {
        $this->LogDebug(__FUNCTION__, $value);
        $list = json_decode($value, true);

        $sort = false;
        $id = 1;
        foreach ($list as &$line) {
            $this->LogDebug(__FUNCTION__, $line['id'] . ' => ' . $id);
            // only if nesseccary
            if ($line['id'] != $id) {
                $line['id'] = $id;
                $sort = true;
            }
            // restore inline arrays
            $line['schedule'] = json_decode($line['schedule'], true);
            // increment
            $id++;
        }
        // everthing changed?
        if ($sort) {
            $this->UpdateFormField('Timetable', 'values', json_encode($list));
        }
    }

    /**
     * Select an list entry.
     *
     * @param string $value json encoded list.
     * @return void
     */
    private function OnSelectList(string $value): void
    {
        $this->LogDebug(__FUNCTION__, $value);
        $list = json_decode($value, true);

        // how many lines in the list?
        $last = count($list);
        // last line has the selected index
        $id = $list[$last - 1];
        $select = [];
        // copy line to last
        for ($index = 0; $index < $last; $index++) {
            if ($list[$index]['id'] == $id) {
                $select = $list[$index];
                $select['schedule'] = json_decode($select['schedule'], true);
                break;
            }
        }
        $this->LogDebug(__FUNCTION__, $select);
        // copy values to fields
        $this->UpdateFormField('SelectedNumber', 'value', $id);
        //'SelectedStatus'
        $this->UpdateFormField('SelectedStatus', 'value', $select['status']);
        //'SelectedRule'
        $this->UpdateFormField('SelectedRule', 'value', $select['rule']);
        //'SelectedAction'
        $this->UpdateFormField('SelectedAction', 'value', $select['action']);
        //'SelectedMonday'
        $this->UpdateFormField('SelectedMonday', 'value', $select['monday']);
        //'SelectedTuesday'
        $this->UpdateFormField('SelectedTuesday', 'value', $select['tuesday']);
        //'SelectedWednesday'
        $this->UpdateFormField('SelectedWednesday', 'value', $select['wednesday']);
        //'SelectedThursday'
        $this->UpdateFormField('SelectedThursday', 'value', $select['thursday']);
        //'SelectedFriday'
        $this->UpdateFormField('SelectedFriday', 'value', $select['friday']);
        //'SelectedSaturday'
        $this->UpdateFormField('SelectedSaturday', 'value', $select['saturday']);
        //'SelectedSunday'
        $this->UpdateFormField('SelectedSunday', 'value', $select['sunday']);
        //'SelectedTime'
        if ($select['schedule']['event'] == self::EVENT_OFF) {
            $this->UpdateFormField('SelectedTime', 'value', $this->EncodeTime($select['time']));
        } else {
            $this->UpdateFormField('SelectedTime', 'value', self::TIME_RESET);
        }
        //'SelectedEarliest'
        $this->UpdateFormField('SelectedEarliest', 'value', $this->EncodeTime($select['schedule']['earliest']));
        //'SelectedEvent'
        $this->UpdateFormField('SelectedEvent', 'value', $select['schedule']['event']);
        //'SelectedLatest'
        $this->UpdateFormField('SelectedLatest', 'value', $this->EncodeTime($select['schedule']['latest']));
        //'SelectedCondition'
        $this->UpdateFormField('SelectedCondition', 'value', $select['condition']);
    }

    /**
     * Update selected list entry.
     *
     * @param string $value json encoded list.
     * @return void
     */
    private function OnUpdateList(string $value): void
    {
        $this->LogDebug(__FUNCTION__, $value);
        $list = json_decode($value, true);

        // how many lines in the list?
        $last = count($list);
        // last line has the new values
        $update = $list[$last - 1];
        // delete the last line
        unset($list[$last - 1]);
        // restore inline arrays
        foreach ($list as &$line) {
            $line['schedule'] = json_decode($line['schedule'], true);
        }
        // reference to selected line
        for ($index = 0; $index < count($list); $index++) {
            if ($list[$index]['id'] == $update['id']) {
                $select = &$list[$index];
                break;
            }
        }
        // State
        $select['status'] = $update['status'];
        // Rule
        $select['rule'] = $update['rule'];
        // Action
        $select['action'] = $update['action'];
        // Weekday
        $select['monday'] = $update['monday'];
        $select['tuesday'] = $update['tuesday'];
        $select['wednesday'] = $update['wednesday'];
        $select['thursday'] = $update['thursday'];
        $select['friday'] = $update['friday'];
        $select['saturday'] = $update['saturday'];
        $select['sunday'] = $update['sunday'];
        // Time
        if ($update['event'] == self::EVENT_OFF) {
            $select['time'] = $this->DecodeTime($update['time']);
        } else {
            $select['time'] = '[' . $this->Translate(self::EVENT_VALUES[$update['event']]) . ']';
        }
        // Schedule
        $select['schedule']['earliest'] = $this->DecodeTime($update['earliest']);
        $select['schedule']['event'] = $update['event'];
        //'SelectedLatest'
        $select['schedule']['latest'] = $this->DecodeTime($update['latest']);
        // Condition
        $select['conditions'] = ($update['condition'] == '[]') ? false : true;
        $select['condition'] = $update['condition'];
        // status row color
        $select['rowColor'] = ($update['status'] == 1) ? self::STATUS_ACTIVE : self::STATUS_INACTIVE;
        // Update list
        $this->UpdateFormField('Timetable', 'values', json_encode($list));
    }

    /**
     * Converts time from string representation to json syntax
     *
     * @param string $value time as string.
     * @return string Time as json formated
     */
    private function EncodeTime($value)
    {
        $part = explode(':', $value);
        if (count($part) === 3) {
            if (($part[0] != '--') && ($part[1] != '--') && ($part[2] != '--')) {
                return vsprintf('{"hour": %d, "minute": %d, "second": %d }', $part);
            }
        }
        return self::TIME_RESET;
    }

    /**
     * Converts time from string representation to json syntax
     *
     * @param string $value time as json.
     * @return string Time as string
     */
    private function DecodeTime($value)
    {
        $time = json_decode($value, true);
        if (($time['hour'] == -1) || ($time['minute'] == -1) || ($time['second'] == -1)) {
            return self::TIME_NONE;
        }
        return vsprintf('%02d:%02d:%02d', $time);
    }

    /**
     * Switch Variable/Script
     *
     * @param boolean $state ON/OFF.
     * @return boolean True if successful, false otherwise
     */
    private function SwitchDevice($state): bool
    {
        $ret = true;
        $this->LogDebug(__FUNCTION__, 'New State: ' . var_export($state, true));
        // Check Script
        $ds = $this->ReadPropertyInteger('DeviceScript');
        if ($ds != 0) {
            if (IPS_ScriptExists($ds)) {
                $rs = IPS_RunScriptEx($ds, ['State' => $state]);
                $this->LogDebug(__FUNCTION__, 'RundScript: ' . $rs);
            } else {
                $this->LogDebug(__FUNCTION__, 'Script #' . $ds . ' doesnt exist!');
            }
        }
        // Check Variables
        $variables = json_decode($this->ReadPropertyString('DeviceVariables'), true);
        $ret = true;
        foreach ($variables as $variable) {
            $ret = @RequestAction($variable['VariableID'], boolval($state));
            if ($ret === false) {
                $this->LogDebug(__FUNCTION__, 'Device #' . $variable['VariableID'] . ' could not be switched by RequestAction!');
                $ret = false;
            }
        }
        if ($ret === false) {
            $this->LogMessage('One or more devices could not be switched!');
        }
        return $ret;
    }
}