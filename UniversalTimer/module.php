<?php

declare(strict_types=1);

/** Generell funktions */
require_once __DIR__ . '/../libs/_traits.php';

/** Namespaced traits */
use Wilkware\UniversalTimer\DebugHelper;
use Wilkware\UniversalTimer\EventHelper;
use Wilkware\UniversalTimer\FormHelper;
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
    use FormHelper;
    use VariableHelper;

    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    /** @var int Status: no active schedule */
    private const STATUS_NO_SCHEDULE = 201;

    /** @var int Status: no switching target */
    private const STATUS_NO_TARGET = 202;

    /** @var int Status: Location Control not found */
    private const STATUS_NO_LOCATION = 203;

    /** @var int Status: conflicting schedules */
    private const STATUS_CONFLICT = 204;

    /** @var int Status: device variable or script missing or not switchable */
    private const STATUS_DEVICE = 205;

    /** @var string Module ID of the Location Control */
    private const LOCATION_MODULE = '{45E97A63-F870-408A-B259-2933F7EABF74}';

    /** @var string Trigger prefix for user defined offsets of the Location Control */
    private const LOCATION_OFFSET = 'LocationOffset:';

    /** @var array<int,string> Bases of the Location Control offsets mapped to the sun event triggers */
    private const LOCATION_BASES = [
        10 => 'AstronomicTwilightStart',
        11 => 'NauticTwilightStart',
        12 => 'CivilTwilightStart',
        13 => 'Sunrise',
        20 => 'Sunset',
        21 => 'CivilTwilightEnd',
        22 => 'NauticTwilightEnd',
        23 => 'AstronomicTwilightEnd',
    ];

    /** @var int Manual switching: no effect */
    private const OVERRIDE_OFF = 0;

    /** @var int Manual switching: pause trigger variables and catch up until the next switching point */
    private const OVERRIDE_PAUSE = 1;

    /** @var int Manual switching: additionally skip the next switching point */
    private const OVERRIDE_SKIP = 2;

    /** @var int Seconds which are looked back to restore the target state */
    private const CATCHUP_WINDOW = 86400;

    /** @var int Seconds in which a device update is regarded as feedback of an own switching */
    private const FEEDBACK_WINDOW = 60;

    /** @var int Number of days which are looked ahead (and back for catch up) */
    private const LOOKAHEAD_DAYS = 8;

    /** @var string Start of day, also "no lower limit" (SelectTime format) */
    private const TIME_START = '{"hour":0,"minute":0,"second":0}';

    /** @var string End of day, also "no upper limit" (SelectTime format) */
    private const TIME_END = '{"hour":23,"minute":59,"second":59}';

    /** @var string Empty individual action (SelectAction format) */
    private const ACTION_NONE = '{"actionID":"","parameters":{}}';

    /** @var string Trigger: fixed time */
    private const TRIGGER_TIME = 'Time';

    /** @var string Trigger: boolean variable */
    private const TRIGGER_VARIABLE = 'Variable';

    /** @var string Trigger type: sun event (incl. Location Control offsets) */
    private const TRIGGER_SUN_TYPE = 'Sun';

    /** @var string Default sun event */
    private const SUN_DEFAULT = 'Sunset';

    /** @var array<string,string> Sun event triggers mapped to the keys of date_sun_info() */
    private const TRIGGER_SUN = [
        'AstronomicTwilightStart' => 'astronomical_twilight_begin',
        'NauticTwilightStart'     => 'nautical_twilight_begin',
        'CivilTwilightStart'      => 'civil_twilight_begin',
        'Sunrise'                 => 'sunrise',
        'Sunset'                  => 'sunset',
        'CivilTwilightEnd'        => 'civil_twilight_end',
        'NauticTwilightEnd'       => 'nautical_twilight_end',
        'AstronomicTwilightEnd'   => 'astronomical_twilight_end',
    ];

    /** @var int Action: switch on */
    private const ACTION_ON = 0;

    /** @var int Action: switch off */
    private const ACTION_OFF = 1;

    /** @var int Action: individual action */
    private const ACTION_CUSTOM = 2;

    /** @var array<int,string> Captions of the actions */
    private const ACTION_CAPTIONS = [
        self::ACTION_ON     => 'Switch on',
        self::ACTION_OFF    => 'Switch off',
        self::ACTION_CUSTOM => 'Individual action',
    ];

    /** @var array<string,string> Captions of the triggers */
    private const TRIGGER_CAPTIONS = [
        'Time'                    => 'Time',
        'AstronomicTwilightStart' => 'Astronomic twilight start',
        'NauticTwilightStart'     => 'Nautic twilight start',
        'CivilTwilightStart'      => 'Civil twilight start',
        'Sunrise'                 => 'Sunrise',
        'Sunset'                  => 'Sunset',
        'CivilTwilightEnd'        => 'Civil twilight end',
        'NauticTwilightEnd'       => 'Nautic twilight end',
        'AstronomicTwilightEnd'   => 'Astronomic twilight end',
        'Variable'                => 'Trigger variable',
    ];

    /** @var array<int,string> Captions of the calendar rules */
    private const CALENDAR_CAPTIONS = [
        0 => 'Always',
        1 => 'On holidays',
        2 => 'On vacations',
        3 => 'On holidays or vacations',
        4 => 'Not on holidays',
        5 => 'Not on vacations',
        6 => 'Not on holidays or vacations',
    ];

    /** @var array<int,string> Short captions of the weekdays (ISO-8601 numbering) */
    private const WEEKDAY_CAPTIONS = [1 => 'MO', 2 => 'TU', 3 => 'WE', 4 => 'TH', 5 => 'FR', 6 => 'SA', 7 => 'SU'];

    /** @var string Date range validation (MM-DD or empty) */
    private const DATE_VALIDATE = '^$|^(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$';

    /** @var array<string,mixed> Default values of the assistant fields */
    private const WIZARD_DEFAULTS = [
        'WizardTemplate'     => 0,
        'WizardName'         => '',
        'WizardTriggerType'  => self::TRIGGER_TIME,
        'WizardTime'         => self::TIME_START,
        'WizardSunEvent'     => self::SUN_DEFAULT,
        'WizardOffset'       => 0,
        'WizardSunEarliest'  => self::TIME_START,
        'WizardSunLatest'    => self::TIME_END,
        'WizardSunSkip'      => false,
        'WizardVariable'     => 0,
        'WizardVarEarliest'  => self::TIME_START,
        'WizardVarLatest'    => self::TIME_END,
        'WizardRandom'       => 0,
        'WizardMonday'       => true,
        'WizardTuesday'      => true,
        'WizardWednesday'    => true,
        'WizardThursday'     => true,
        'WizardFriday'       => true,
        'WizardSaturday'     => true,
        'WizardSunday'       => true,
        'WizardCalendar'     => 0,
        'WizardDateFrom'     => '',
        'WizardDateTo'       => '',
        'WizardCondition'    => '[]',
        'WizardAction'       => self::ACTION_ON,
        'WizardActionCustom' => self::ACTION_NONE,
    ];

    /** @var array<int,array<string,mixed>> Templates of the assistant (differences to the defaults) */
    private const WIZARD_TEMPLATES = [
        1 => [
            'WizardName'        => 'Light on at dusk',
            'WizardTriggerType' => self::TRIGGER_SUN_TYPE,
            'WizardSunEvent'    => 'CivilTwilightEnd',
        ],
        2 => [
            'WizardName'   => 'Light off at a fixed time',
            'WizardTime'   => '{"hour":23,"minute":0,"second":0}',
            'WizardAction' => self::ACTION_OFF,
        ],
        3 => [
            'WizardName'        => 'Shutters up on workdays',
            'WizardTriggerType' => self::TRIGGER_SUN_TYPE,
            'WizardSunEvent'    => 'Sunrise',
            'WizardSunEarliest' => '{"hour":6,"minute":30,"second":0}',
            'WizardSunLatest'   => '{"hour":8,"minute":0,"second":0}',
            'WizardSaturday'    => false,
            'WizardSunday'      => false,
            'WizardCalendar'    => 4,
        ],
        4 => [
            'WizardName'        => 'Presence simulation in the evening',
            'WizardTriggerType' => self::TRIGGER_SUN_TYPE,
            'WizardSunEvent'    => 'Sunset',
            'WizardOffset'      => 30,
            'WizardRandom'      => 20,
        ],
    ];

    /** @var array<int,string> Weekday keys of a timetable entry (ISO-8601 numbering) */
    private const WEEKDAYS = [1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 7 => 'sunday'];

    /** @var array<string,mixed> Default values of a timetable entry (v3) */
    private const SCHEDULE_DEFAULTS = [
        'active'          => true,
        'name'            => '',
        'monday'          => true,
        'tuesday'         => true,
        'wednesday'       => true,
        'thursday'        => true,
        'friday'          => true,
        'saturday'        => true,
        'sunday'          => true,
        'triggerType'     => self::TRIGGER_TIME,
        'trigger'         => self::TRIGGER_TIME,
        'time'            => self::TIME_START,
        'sunEvent'        => self::SUN_DEFAULT,
        'action'          => self::ACTION_ON,
        'calendar'        => 0,
        'offset'          => 0,
        'earliest'        => self::TIME_START,
        'latest'          => self::TIME_END,
        'limitSkip'       => false,
        'random'          => 0,
        'triggerVariable' => 0,
        'windowStart'     => self::TIME_START,
        'windowEnd'       => self::TIME_END,
        'dateFrom'        => '',
        'dateTo'          => '',
        'condition'       => '[]',
        'actionCustom'    => self::ACTION_NONE,
    ];

    // -------------------------------------------------------------------------
    // Presentations
    // -------------------------------------------------------------------------

    /** @var array<string,mixed> Switch presentation */
    private const UT_PRESENTATION_SWITCH = [
        'PRESENTATION' => VARIABLE_PRESENTATION_SWITCH,
    ];

    /** @var array<string,mixed> Date/time presentation (weekday, day, month, hours and minutes) */
    private const UT_PRESENTATION_DATETIME = [
        'PRESENTATION'    => VARIABLE_PRESENTATION_DATE_TIME,
        'DATE'            => 2,
        'DAY_OF_THE_WEEK' => true,
        'MONTH_TEXT'      => false,
        'TIME'            => 1,
    ];

    /** @var array<string,mixed> Text presentation */
    private const UT_PRESENTATION_TEXT = [
        'PRESENTATION' => VARIABLE_PRESENTATION_VALUE_PRESENTATION,
        'ICON'         => 'calendar-clock',
    ];

    // -------------------------------------------------------------------------
    // Properties
    // -------------------------------------------------------------------------

    /** @var array<string,array<string,mixed>>|null Cache of the Location Control offsets (per call) */
    private ?array $offsetCache = null;

    // -------------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------------

    /**
     * In contrast to Construct, this function is called only once when creating the instance and starting Symcon.
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

        // Calendar
        $this->RegisterPropertyInteger('CalendarHoliday', 0);
        $this->RegisterPropertyInteger('CalendarVacation', 0);
        $this->RegisterPropertyInteger('CalendarLock', 0);

        // Device
        $this->RegisterPropertyString('DeviceVariables', '[]');
        $this->RegisterPropertyInteger('DeviceScript', 0);

        // Settings
        $this->RegisterPropertyBoolean('SettingsSwitch', false);
        $this->RegisterPropertyBoolean('SettingsCatchUp', true);
        $this->RegisterPropertyInteger('SettingsOverrideMode', self::OVERRIDE_OFF);
        $this->RegisterPropertyBoolean('SettingsDirect', false);

        // Deprecated (v2), migrated into the timetable
        $this->RegisterPropertyInteger('SettingsTrigger', 0);
        // Deprecated (v3 prototype), migrated into SettingsOverrideMode
        $this->RegisterPropertyBoolean('SettingsOverride', false);

        // Timer
        $this->RegisterTimer('SwitchTimer', 0, "IPS_RequestAction(\$_IPS['TARGET'], 'Schedule', 0);");

        // Kernel ready
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
    }

    /**
     * This function is called when deleting the instance during operation and when updating via "Module Control".
     * The function is not called when exiting Symcon.
     *
     * @return void
     */
    public function Destroy(): void
    {
        //Never delete this line!
        parent::Destroy();
    }

    /**
     * Migrates the configuration of older module versions.
     * v2: timetable entries with status/rule/schedule and a global external trigger.
     *
     * @param string $json Configuration data as JSON
     * @return string Migrated configuration data as JSON
     */
    public function Migrate(string $json): string
    {
        //Never delete this line!
        parent::Migrate($json);

        // Decoded as objects, so that empty objects (e.g. "attributes": {}) are not written back as arrays
        $data = json_decode($json);
        if (!is_object($data) || !isset($data->configuration) || !is_object($data->configuration)) {
            return $json;
        }
        $config = $data->configuration;
        $changed = false;

        // Override checkbox (v3 prototype) => override mode
        if ((bool) ($config->SettingsOverride ?? false)) {
            $config->SettingsOverrideMode = self::OVERRIDE_PAUSE;
            $config->SettingsOverride = false;
            $changed = true;
        }

        $list = json_decode((string) ($config->Timetable ?? '[]'), true);
        if (!is_array($list)) {
            return $changed ? json_encode($data) : $json;
        }
        $trigger = (int) ($config->SettingsTrigger ?? 0);
        $migrated = false;
        foreach ($list as &$line) {
            if (!is_array($line)) {
                continue;
            }
            if (array_key_exists('schedule', $line)) {
                $line = $this->MigrateScheduleV2($line, $trigger);
                $migrated = true;
            }
            // v3 prototype stored unset times as -1 and an empty action as ''
            foreach (['time' => self::TIME_START, 'earliest' => self::TIME_START, 'latest' => self::TIME_END] as $key => $default) {
                if (isset($line[$key]) && str_contains((string) $line[$key], '-1')) {
                    $line[$key] = $default;
                    $migrated = true;
                }
            }
            if (isset($line['actionCustom']) && ($line['actionCustom'] === '')) {
                $line['actionCustom'] = self::ACTION_NONE;
                $migrated = true;
            }
            // v3 prototype had no trigger type
            if (!isset($line['triggerType'])) {
                $line = $this->NormalizeSchedule($line);
                $migrated = true;
            }
        }
        unset($line);
        if ($migrated) {
            $config->Timetable = json_encode(array_values($list));
            if ($trigger !== 0) {
                $config->SettingsTrigger = 0;
            }
            $changed = true;
        }

        return $changed ? json_encode($data) : $json;
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
        $version = sprintf('v%s.%d', $lib['Version'], $lib['Build']);
        $this->ModifyFormElement($form['actions'], 'Version', function (array &$element) use ($version): void
        {
            $element['caption'] = $version;
        });

        // User defined offsets of the Location Control as additional sun events
        $offsets = $this->TriggerOptions();
        if (!empty($offsets)) {
            $this->ModifyFormElementDeep($form, 'WizardSunEvent', function (array &$element) use ($offsets): void
            {
                $element['options'] = array_merge($element['options'], $offsets);
            });
            $this->ModifyFormElementDeep($form, 'Timetable', function (array &$element) use ($offsets): void
            {
                foreach ($element['columns'] as &$column) {
                    if ($column['name'] === 'trigger') {
                        $column['edit']['options'] = array_merge($column['edit']['options'], $offsets);
                    }
                }
            });
        }

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

        // Additionally switch
        $switch = $this->ReadPropertyBoolean('SettingsSwitch');
        $this->MaintainVariable('switch_proxy', $this->Translate('Switch'), VARIABLETYPE_BOOLEAN, self::UT_PRESENTATION_SWITCH, 0, $switch);
        if ($switch) {
            $this->EnableAction('switch_proxy');
        }

        // Status variables
        $created = (@IPS_GetObjectIDByIdent('Automatic', $this->InstanceID) === false);
        $this->RegisterVariableBoolean('Automatic', $this->Translate('Automatic'), self::UT_PRESENTATION_SWITCH, 1);
        $this->EnableAction('Automatic');
        if ($created) {
            $this->SetValueBoolean('Automatic', true);
        }
        $this->RegisterVariableInteger('NextSwitch', $this->Translate('Next switching'), self::UT_PRESENTATION_DATETIME, 2);
        $this->RegisterVariableString('NextAction', $this->Translate('Next action'), self::UT_PRESENTATION_TEXT, 3);
        $this->RegisterVariableInteger('LastSwitch', $this->Translate('Last switching'), self::UT_PRESENTATION_DATETIME, 4);

        // Messages of trigger and device variables
        foreach ($this->GetMessageList() as $sender => $messages) {
            if (in_array(VM_UPDATE, $messages, true)) {
                $this->UnregisterMessage($sender, VM_UPDATE);
            }
        }
        foreach (array_merge($this->GetTriggerVariables(), $this->GetDeviceVariables()) as $vid) {
            if (IPS_VariableExists($vid)) {
                $this->RegisterMessage($vid, VM_UPDATE);
            }
        }

        // References
        foreach ($this->GetReferenceList() as $reference) {
            $this->UnregisterReference($reference);
        }
        foreach ($this->GetReferencedObjects() as $reference) {
            $this->RegisterReference($reference);
        }

        $this->SetStatus($this->CheckConfiguration());

        if (IPS_GetKernelRunlevel() === KR_READY) {
            $this->Initialize(false);
        }
    }

    /**
     * The content of the function can be overwritten in order to carry out own reactions to certain messages.
     * The function is only called for registered MessageIDs/SenderIDs combinations.
     *
     * data[0] = new value
     * data[1] = value changed?
     * data[2] = old value
     * data[3] = timestamp.
     *
     * @param int $timestamp Continuous counter timestamp
     * @param int $sender Sender ID
     * @param int $message ID of the message
     * @param array<mixed> $data Data of the message
     * @return void
     */
    public function MessageSink(int $timestamp, int $sender, int $message, array $data): void
    {
        switch ($message) {
            case IPS_KERNELSTARTED:
                $this->Initialize(true);
                break;
            case VM_UPDATE:
                if (!$data[1]) {
                    break;
                }
                if (in_array($sender, $this->GetDeviceVariables(), true)) {
                    $this->OnDeviceUpdate($sender, (bool) $data[0]);
                }
                if ((bool) $data[0] && in_array($sender, $this->GetTriggerVariables(), true)) {
                    $this->OnTriggerVariable($sender);
                }
                break;
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
        $this->LogDebug(__FUNCTION__, $ident . ' => ' . var_export($value, true));
        switch ($ident) {
            case 'Schedule':
                $this->OnSchedule();
                break;
            case 'Preview':
                $this->ShowPreview();
                break;
            case 'ListChanged':
                $this->OnListChanged((string) $value);
                break;
            case 'WizardTemplate':
                $this->ApplyWizardTemplate((int) $value);
                break;
            case 'WizardSummary':
                $row = $this->WizardRow(json_decode((string) $value, true));
                $this->UpdateFormField('WizardSummary', 'values', json_encode($this->ScheduleSummary($row)));
                break;
            case 'WizardFinish':
                $this->OnWizardFinish((string) $value);
                break;
            case 'TestSwitch':
                if (!$this->SwitchDevice((bool) $value)) {
                    $this->EchoMessage('One or more devices could not be switched!');
                }
                break;
            case 'Automatic':
                $this->SetValueBoolean($ident, (bool) $value);
                break;
            case 'switch_proxy':
                if ($this->SwitchDevice((bool) $value)) {
                    $this->SetValueBoolean($ident, (bool) $value);
                }
                $this->StartOverride();
                break;
            default:
                break;
        }
    }

    /**
     * Returns the edit dialog of a timetable entry.
     * Trigger and action are radio buttons, the dependent fields are only shown for the selected option.
     * Internal function, called by the timetable list of the configuration form.
     *
     * @return string Dialog elements as JSON
     */
    public function GetScheduleForm(): string
    {
        $options = function (array $captions): array
        {
            $options = [];
            foreach ($captions as $value => $caption) {
                $options[] = ['caption' => $this->Translate($caption), 'value' => $value];
            }
            return $options;
        };
        $section = fn (string $caption): array => ['type' => 'Label', 'caption' => $this->Translate($caption), 'bold' => true];
        $time = fn (string $name, string $caption, string $value): array => ['type' => 'SelectTime', 'name' => $name, 'caption' => $this->Translate($caption), 'value' => $value];
        $sunEvents = array_filter(self::TRIGGER_CAPTIONS, fn (string $key): bool => isset(self::TRIGGER_SUN[$key]), ARRAY_FILTER_USE_KEY);
        $days = [];
        foreach (self::WEEKDAYS as $number => $day) {
            $days[] = ['type' => 'CheckBox', 'name' => $day, 'caption' => $this->Translate(self::WEEKDAY_CAPTIONS[$number]), 'value' => true];
        }
        $form = [
            [
                'type'  => 'RowLayout',
                'items' => [
                    ['type' => 'ValidationTextBox', 'name' => 'name', 'caption' => $this->Translate('Name'), 'value' => '', 'width' => '300px'],
                    ['type' => 'CheckBox', 'name' => 'active', 'caption' => $this->Translate('Active'), 'value' => true],
                ],
            ],
            $section('When?'),
            [
                'type'          => 'RadioButtonGroup',
                'name'          => 'triggerType',
                'value'         => self::TRIGGER_TIME,
                'width'         => '100%',
                'itemsPosition' => 1,
                'options'       => [
                    [
                        'caption' => $this->Translate('Fixed time'),
                        'value'   => self::TRIGGER_TIME,
                        'items'   => [$time('time', 'Time', self::TIME_START)],
                    ],
                    [
                        'caption' => $this->Translate('Sun event'),
                        'value'   => self::TRIGGER_SUN_TYPE,
                        'items'   => [
                            [
                                'type'  => 'RowLayout',
                                'items' => [
                                    ['type' => 'Select', 'name' => 'sunEvent', 'caption' => $this->Translate('Event'), 'value' => self::SUN_DEFAULT, 'width' => '300px', 'options' => array_merge($options($sunEvents), $this->TriggerOptions())],
                                    ['type' => 'NumberSpinner', 'name' => 'offset', 'caption' => $this->Translate('Offset (minutes)'), 'value' => 0, 'minimum' => -720, 'maximum' => 720, 'suffix' => ' min'],
                                ],
                            ],
                            [
                                'type'  => 'RowLayout',
                                'items' => [
                                    $time('earliest', 'Earliest', self::TIME_START),
                                    $time('latest', 'Latest', self::TIME_END),
                                    ['type' => 'CheckBox', 'name' => 'limitSkip', 'caption' => $this->Translate('Do not switch outside'), 'value' => false],
                                ],
                            ],
                        ],
                    ],
                    [
                        'caption' => $this->Translate('Trigger variable'),
                        'value'   => self::TRIGGER_VARIABLE,
                        'items'   => [
                            ['type' => 'SelectVariable', 'name' => 'triggerVariable', 'caption' => $this->Translate('Trigger variable'), 'value' => 0, 'validVariableTypes' => [0], 'width' => '300px'],
                            [
                                'type'  => 'RowLayout',
                                'items' => [
                                    $time('windowStart', 'Earliest', self::TIME_START),
                                    $time('windowEnd', 'Latest', self::TIME_END),
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            ['type' => 'NumberSpinner', 'name' => 'random', 'caption' => $this->Translate('Random deviation (± minutes, not for trigger variable)'), 'value' => 0, 'minimum' => 0, 'maximum' => 120, 'suffix' => ' min'],
            $section('On which days?'),
            ['type' => 'RowLayout', 'items' => $days],
            [
                'type'  => 'RowLayout',
                'items' => [
                    ['type' => 'Select', 'name' => 'calendar', 'caption' => $this->Translate('Rule'), 'value' => 0, 'width' => '300px', 'options' => $options(self::CALENDAR_CAPTIONS)],
                    ['type' => 'ValidationTextBox', 'name' => 'dateFrom', 'caption' => $this->Translate('Valid from (MM-DD)'), 'value' => '', 'validate' => self::DATE_VALIDATE],
                    ['type' => 'ValidationTextBox', 'name' => 'dateTo', 'caption' => $this->Translate('Valid until (MM-DD)'), 'value' => '', 'validate' => self::DATE_VALIDATE],
                ],
            ],
            $section('Under which condition?'),
            ['type' => 'SelectCondition', 'name' => 'condition', 'value' => '[]', 'multi' => true],
            $section('What should happen?'),
            [
                'type'          => 'RadioButtonGroup',
                'name'          => 'action',
                'value'         => self::ACTION_ON,
                'width'         => '100%',
                'itemsPosition' => 1,
                'options'       => [
                    ['caption' => $this->Translate(self::ACTION_CAPTIONS[self::ACTION_ON]), 'value' => self::ACTION_ON],
                    ['caption' => $this->Translate(self::ACTION_CAPTIONS[self::ACTION_OFF]), 'value' => self::ACTION_OFF],
                    [
                        'caption' => $this->Translate(self::ACTION_CAPTIONS[self::ACTION_CUSTOM]),
                        'value'   => self::ACTION_CUSTOM,
                        'items'   => [['type' => 'SelectAction', 'name' => 'actionCustom', 'value' => self::ACTION_NONE]],
                    ],
                ],
            ],
        ];
        return json_encode($form);
    }

    /**
     * Starts the timer after kernel start or configuration changes, restores the target state if requested.
     *
     * @param bool $restart True after a kernel start, false after applying the configuration
     * @return void
     */
    private function Initialize(bool $restart): void
    {
        $this->SetBuffer('LastRun', (string) time());
        if ($restart) {
            $this->SetBuffer('OverrideUntil', '0');
        }
        if ($this->ReadPropertyBoolean('InstanceActive') && $this->ReadPropertyBoolean('SettingsCatchUp')) {
            $this->CatchUp($restart);
        }
        $this->Recalculate();
    }

    /**
     * Manual switching detected: starts the override until the next switching point (depending on the mode).
     *
     * @return void
     */
    private function StartOverride(): void
    {
        if ($this->ReadPropertyInteger('SettingsOverrideMode') === self::OVERRIDE_OFF) {
            return;
        }
        $this->SetBuffer('OverrideUntil', (string) (int) $this->GetBuffer('NextSwitch'));
        $this->LogDebug(__FUNCTION__, 'Override until ' . date('d.m.Y H:i:s', (int) $this->GetBuffer('NextSwitch')));
    }

    /**
     * Timer callback: executes all due switching points and schedules the next one.
     *
     * @return void
     */
    private function OnSchedule(): void
    {
        $now = time();
        $last = (int) $this->GetBuffer('LastRun');
        if ($last === 0) {
            $last = $now - 1;
        }
        $points = $this->GetSwitchPoints($last + 1, $now + 1);
        $override = (int) $this->GetBuffer('OverrideUntil');
        $skip = ($this->ReadPropertyInteger('SettingsOverrideMode') === self::OVERRIDE_SKIP);
        foreach ($points as $point) {
            if ($skip && ($point['time'] <= $override)) {
                // Manual switching takes precedence over this switching point
                $this->LogDebug(__FUNCTION__, $this->ScheduleName($point['schedule'], $point['index']) . ' skipped (manual override)');
            } else {
                $this->ExecuteSchedule($point['schedule'], $point['index']);
            }
            $last = max($last, $point['time']);
        }
        if (!empty($points)) {
            $this->SetBuffer('OverrideUntil', '0');
        }
        $this->SetBuffer('LastRun', (string) max($last, $now));
        $this->Recalculate();
    }

    /**
     * Calculates the next switching point, sets the timer and updates the status variables.
     *
     * @return void
     */
    private function Recalculate(): void
    {
        if (!$this->ReadPropertyBoolean('InstanceActive')) {
            $this->SetTimerInterval('SwitchTimer', 0);
            $this->UpdateNextSwitch(null);
            return;
        }
        $now = time();
        $from = max($now, (int) $this->GetBuffer('LastRun')) + 1;
        $points = $this->GetSwitchPoints($from, $now + self::LOOKAHEAD_DAYS * 86400);
        $next = $points[0] ?? null;
        // Recalculate at least once a day (sun times, calendar)
        $midnight = mktime(0, 0, 5, (int) date('n', $now), (int) date('j', $now) + 1, (int) date('Y', $now));
        $target = ($next !== null) ? min($next['time'], $midnight) : $midnight;
        $this->SetTimerInterval('SwitchTimer', max(1, $target - $now) * 1000);
        $this->UpdateNextSwitch($next);
        $this->LogDebug(__FUNCTION__, 'Timer: ' . date('d.m.Y H:i:s', $target) . (($next !== null) ? ', next: ' . date('d.m.Y H:i:s', $next['time']) . ' #' . ($next['index'] + 1) : ', no switching point'));
    }

    /**
     * Writes the next switching point into buffer and status variables.
     *
     * @param array<string,mixed>|null $next Next switching point or null
     * @return void
     */
    private function UpdateNextSwitch(?array $next): void
    {
        $this->SetBuffer('NextSwitch', (string) ($next['time'] ?? 0));
        $this->SetValueInteger('NextSwitch', $next['time'] ?? 0);
        $text = '';
        if ($next !== null) {
            $text = $this->Translate(self::ACTION_CAPTIONS[(int) $next['schedule']['action']] ?? '') . ' (' . $this->ScheduleName($next['schedule'], $next['index']) . ')';
        }
        $this->SetValueString('NextAction', $text);
    }

    /**
     * Restores the target state, i.e. executes the last switching point of the last 24 hours which passes all checks.
     * Devices are only switched if their state differs; individual actions and devices without known state
     * are only restored after a restart.
     *
     * @param bool $restart True after a kernel start, false after applying the configuration
     * @return void
     */
    private function CatchUp(bool $restart): void
    {
        $now = time();
        $points = array_reverse($this->GetSwitchPoints($now - self::CATCHUP_WINDOW, $now));
        foreach ($points as $point) {
            $schedule = $point['schedule'];
            $name = $this->ScheduleName($schedule, $point['index']);
            if ($this->SkipReason($schedule, true) !== '') {
                continue;
            }
            // Target state found: switch only if necessary
            $action = (int) $schedule['action'];
            if ($action === self::ACTION_CUSTOM) {
                if (!$restart) {
                    $this->LogDebug(__FUNCTION__, $name . ': individual action is only restored after a restart');
                    return;
                }
            } else {
                $devices = $this->GetDeviceVariables();
                if (empty($devices) && !$restart) {
                    $this->LogDebug(__FUNCTION__, $name . ': state unknown (no devices), only restored after a restart');
                    return;
                }
                $state = ($action === self::ACTION_ON);
                $differs = empty($devices);
                foreach ($devices as $vid) {
                    $differs = $differs || !IPS_VariableExists($vid) || ((bool) GetValue($vid) !== $state);
                }
                if (!$differs) {
                    $this->LogDebug(__FUNCTION__, $name . ': devices already in target state');
                    return;
                }
            }
            $this->LogDebug(__FUNCTION__, $name . ': restore target state');
            $this->ExecuteSchedule($schedule, $point['index'], true);
            return;
        }
    }

    /**
     * A trigger variable has changed to TRUE.
     *
     * @param int $vid Variable ID
     * @return void
     */
    private function OnTriggerVariable(int $vid): void
    {
        if (!$this->ReadPropertyBoolean('InstanceActive')) {
            return;
        }
        $now = time();
        $day = $this->DayStart($now);
        foreach ($this->GetSchedules() as $index => $schedule) {
            if (!$schedule['active'] || ($schedule['trigger'] !== self::TRIGGER_VARIABLE) || ((int) $schedule['triggerVariable'] !== $vid)) {
                continue;
            }
            if (!$this->IsScheduleDay($schedule, $day)) {
                continue;
            }
            $start = $this->DayTime($day, (string) $schedule['windowStart']);
            $end = $this->DayTime($day, (string) $schedule['windowEnd']);
            if (($now < $start) || ($now > $end)) {
                $this->LogDebug(__FUNCTION__, 'Schedule #' . ($index + 1) . ' outside of time window');
                continue;
            }
            $this->ExecuteSchedule($schedule, $index, true);
        }
    }

    /**
     * Checks all rules of a schedule and executes its action.
     *
     * @param array<string,mixed> $schedule Timetable entry
     * @param int $index Position in the timetable
     * @param bool $respectOverride Skip while a manual override is active
     * @return bool True if the action was executed
     */
    private function ExecuteSchedule(array $schedule, int $index, bool $respectOverride = false): bool
    {
        $name = $this->ScheduleName($schedule, $index);
        $reason = $this->SkipReason($schedule, $respectOverride);
        if ($reason !== '') {
            $this->LogDebug(__FUNCTION__, $name . ' skipped (' . $reason . ')');
            return false;
        }
        $this->LogDebug(__FUNCTION__, $name . ' => ' . self::ACTION_CAPTIONS[(int) $schedule['action']]);
        switch ((int) $schedule['action']) {
            case self::ACTION_ON:
            case self::ACTION_OFF:
                $state = ((int) $schedule['action'] === self::ACTION_ON);
                $this->SwitchDevice($state);
                if ($this->ReadPropertyBoolean('SettingsSwitch')) {
                    $this->SetValueBoolean('switch_proxy', $state);
                }
                break;
            case self::ACTION_CUSTOM:
                $action = json_decode((string) $schedule['actionCustom'], true);
                if (!$this->HasCustomAction((string) $schedule['actionCustom'])) {
                    $this->LogDebug(__FUNCTION__, $name . ' has no individual action');
                    return false;
                }
                $error = IPS_RunActionWait($action['actionID'], $action['parameters'] ?? []);
                if ($error !== '') {
                    $this->LogMessage($name . ': ' . $error, KL_WARNING);
                }
                break;
        }
        $this->SetValueInteger('LastSwitch', time());
        return true;
    }

    /**
     * Checks automatic, lock, manual override, calendar rule and condition of a schedule.
     *
     * @param array<string,mixed> $schedule Timetable entry
     * @param bool $respectOverride Consider an active manual override
     * @return string Reason why the schedule must not switch, empty if it may switch
     */
    private function SkipReason(array $schedule, bool $respectOverride): string
    {
        if (!$this->GetValue('Automatic')) {
            return 'automatic off';
        }
        if ($this->ReadBoolVariable($this->ReadPropertyInteger('CalendarLock'))) {
            return 'locked';
        }
        if ($respectOverride && ((int) $this->GetBuffer('OverrideUntil') > time())) {
            return 'manual override';
        }
        if (!$this->CheckCalendar((int) $schedule['calendar'])) {
            return 'calendar rule';
        }
        if (!$this->CheckCondition((string) $schedule['condition'])) {
            return 'condition';
        }
        return '';
    }

    /**
     * Calculates all time based switching points within a period, sorted by time and list position.
     *
     * @param int $from Start of the period (timestamp, inclusive)
     * @param int $to End of the period (timestamp, inclusive)
     * @return array<int,array<string,mixed>> List of ['time', 'index', 'schedule']
     */
    private function GetSwitchPoints(int $from, int $to): array
    {
        $schedules = $this->GetSchedules();
        $location = $this->GetLocation();
        $points = [];
        for ($day = $this->DayStart($from); $day <= $to; $day = $this->NextDay($day)) {
            foreach ($schedules as $index => $schedule) {
                if (!$schedule['active']) {
                    continue;
                }
                $time = $this->CalculateSwitchTime($schedule, $index, $day, $location);
                if (($time !== null) && ($time >= $from) && ($time <= $to)) {
                    $points[] = ['time' => $time, 'index' => $index, 'schedule' => $schedule];
                }
            }
        }
        usort($points, fn (array $a, array $b): int => [$a['time'], $a['index']] <=> [$b['time'], $b['index']]);
        return $points;
    }

    /**
     * Calculates the switching time of a schedule for a given day.
     *
     * @param array<string,mixed> $schedule Timetable entry
     * @param int $index Position in the timetable
     * @param int $day Start of the day (timestamp)
     * @param array<string,float>|null $location Latitude and longitude
     * @return int|null Timestamp or null if the schedule does not switch on this day
     */
    private function CalculateSwitchTime(array $schedule, int $index, int $day, ?array $location): ?int
    {
        if (!$this->IsScheduleDay($schedule, $day)) {
            return null;
        }
        $trigger = (string) $schedule['trigger'];
        if ($trigger === self::TRIGGER_TIME) {
            return $this->DayTime($day, (string) $schedule['time']) + $this->RandomDeviation($schedule, $index, $day);
        }
        $event = $this->ResolveSunTrigger($trigger);
        if (($event === null) || ($location === null)) {
            return null;
        }
        $noon = mktime(12, 0, 0, (int) date('n', $day), (int) date('j', $day), (int) date('Y', $day));
        $sun = date_sun_info($noon, $location['latitude'], $location['longitude']);
        $time = $sun[$event['key']] ?? false;
        // Polar day or night
        if (!is_int($time)) {
            return null;
        }
        $time += $event['offset'] + (int) $schedule['offset'] * 60 + $this->RandomDeviation($schedule, $index, $day);
        $earliest = $this->DayTime($day, (string) $schedule['earliest']);
        $latest = $this->DayTime($day, (string) $schedule['latest']);
        if (($earliest <= $latest) && (($time < $earliest) || ($time > $latest))) {
            // Outside the limits: either skip this day or shift to the limit
            if ($schedule['limitSkip']) {
                return null;
            }
            $time = min(max($time, $earliest), $latest);
        }
        return $time;
    }

    /**
     * Returns the random deviation of a schedule for a day.
     * Deterministic per day and schedule, so a recalculation does not move the switching point.
     *
     * @param array<string,mixed> $schedule Timetable entry
     * @param int $index Position in the timetable
     * @param int $day Start of the day (timestamp)
     * @return int Deviation in seconds
     */
    private function RandomDeviation(array $schedule, int $index, int $day): int
    {
        $random = (int) $schedule['random'];
        if ($random <= 0) {
            return 0;
        }
        $hash = crc32($this->InstanceID . '|' . $index . '|' . date('Ymd', $day));
        return (($hash % (2 * $random + 1)) - $random) * 60;
    }

    /**
     * Checks weekday and date range of a schedule.
     *
     * @param array<string,mixed> $schedule Timetable entry
     * @param int $day Start of the day (timestamp)
     * @return bool True if the schedule is valid on this day
     */
    private function IsScheduleDay(array $schedule, int $day): bool
    {
        if (!$schedule[self::WEEKDAYS[(int) date('N', $day)]]) {
            return false;
        }
        $from = ($schedule['dateFrom'] !== '') ? (string) $schedule['dateFrom'] : '01-01';
        $to = ($schedule['dateTo'] !== '') ? (string) $schedule['dateTo'] : '12-31';
        $date = date('m-d', $day);
        if ($from <= $to) {
            return ($date >= $from) && ($date <= $to);
        }
        // Range across the turn of the year, e.g. 12-01 to 01-06
        return ($date >= $from) || ($date <= $to);
    }

    /**
     * Checks the calendar rule against the holiday and vacation variables.
     *
     * @param int $rule Calendar rule (0 = always ... 6 = not on holidays or vacations)
     * @return bool True if the rule is fulfilled
     */
    private function CheckCalendar(int $rule): bool
    {
        if ($rule === 0) {
            return true;
        }
        $holiday = $this->ReadBoolVariable($this->ReadPropertyInteger('CalendarHoliday'));
        $vacation = $this->ReadBoolVariable($this->ReadPropertyInteger('CalendarVacation'));
        return match ($rule) {
            1       => $holiday,
            2       => $vacation,
            3       => $holiday || $vacation,
            4       => !$holiday,
            5       => !$vacation,
            6       => !$holiday && !$vacation,
            default => true,
        };
    }

    /**
     * Checks a condition (SelectCondition format).
     *
     * @param string $condition Condition as JSON
     * @return bool True if no condition is set or it is fulfilled
     */
    private function CheckCondition(string $condition): bool
    {
        if (($condition === '') || ($condition === '[]')) {
            return true;
        }
        return IPS_IsConditionPassing($condition);
    }

    /**
     * Shows the switching points of the next 7 days in a popup.
     *
     * @return void
     */
    private function ShowPreview(): void
    {
        $now = time();
        $rows = [];
        foreach ($this->GetSwitchPoints($now, $now + 7 * 86400) as $point) {
            $rows[] = [
                'date'   => $this->Translate(date('D', $point['time'])) . ' ' . date('d.m.', $point['time']),
                'time'   => date('H:i', $point['time']),
                'name'   => $this->ScheduleName($point['schedule'], $point['index']),
                'action' => $this->Translate(self::ACTION_CAPTIONS[(int) $point['schedule']['action']] ?? ''),
                'note'   => $this->PreviewNote($point['schedule']),
            ];
        }
        foreach ($this->GetSchedules() as $index => $schedule) {
            if ($schedule['active'] && ($schedule['trigger'] === self::TRIGGER_VARIABLE)) {
                $rows[] = [
                    'date'   => '-',
                    'time'   => $this->FormatTime((string) $schedule['earliest']) . ' - ' . $this->FormatTime((string) $schedule['latest']),
                    'name'   => $this->ScheduleName($schedule, $index),
                    'action' => $this->Translate(self::ACTION_CAPTIONS[(int) $schedule['action']] ?? ''),
                    'note'   => trim($this->Translate('On change of the trigger variable') . ' ' . $this->PreviewNote($schedule)),
                ];
            }
        }
        if (empty($rows)) {
            $this->EchoMessage('No switching points in the next 7 days.');
            return;
        }
        $this->UpdateFormField('PreviewList', 'values', json_encode($rows));
        $this->UpdateFormField('PreviewPopup', 'visible', true);
    }

    /**
     * Returns the hints for the preview of a schedule.
     *
     * @param array<string,mixed> $schedule Timetable entry
     * @return string Hints
     */
    private function PreviewNote(array $schedule): string
    {
        $notes = [];
        if ((int) $schedule['calendar'] !== 0) {
            $notes[] = $this->Translate('Calendar rule');
        }
        if (!in_array((string) $schedule['condition'], ['', '[]'], true)) {
            $notes[] = $this->Translate('Condition');
        }
        if (empty($notes)) {
            return '';
        }
        return '(' . implode(', ', $notes) . ' ' . $this->Translate('checked at switching time') . ')';
    }

    /**
     * Fills the assistant fields with the values of a template.
     *
     * @param int $template Template number (0 = none)
     * @return void
     */
    private function ApplyWizardTemplate(int $template): void
    {
        $values = array_merge(self::WIZARD_DEFAULTS, self::WIZARD_TEMPLATES[$template] ?? []);
        $values['WizardTemplate'] = $template;
        $values['WizardName'] = ($values['WizardName'] !== '') ? $this->Translate($values['WizardName']) : '';
        foreach ($values as $field => $value) {
            $this->UpdateFormField($field, 'value', $value);
        }
    }

    /**
     * Builds a timetable entry from the assistant fields.
     *
     * @param array<string,mixed>|null $fields Values of the assistant fields
     * @return array<string,mixed> Timetable entry
     */
    private function WizardRow(?array $fields): array
    {
        $fields = array_merge(self::WIZARD_DEFAULTS, $fields ?? []);
        $type = (string) $fields['WizardTriggerType'];
        $row = self::SCHEDULE_DEFAULTS;
        $row['name'] = trim((string) $fields['WizardName']);
        $row['triggerType'] = $type;
        if ($type === self::TRIGGER_TIME) {
            $row['time'] = (string) $fields['WizardTime'];
        } elseif ($type === self::TRIGGER_SUN_TYPE) {
            $row['sunEvent'] = (string) $fields['WizardSunEvent'];
            $row['offset'] = (int) $fields['WizardOffset'];
            $row['earliest'] = (string) $fields['WizardSunEarliest'];
            $row['latest'] = (string) $fields['WizardSunLatest'];
            $row['limitSkip'] = (bool) $fields['WizardSunSkip'];
        } else {
            $row['triggerVariable'] = (int) $fields['WizardVariable'];
            $row['windowStart'] = (string) $fields['WizardVarEarliest'];
            $row['windowEnd'] = (string) $fields['WizardVarLatest'];
        }
        $row['random'] = ($type === self::TRIGGER_VARIABLE) ? 0 : (int) $fields['WizardRandom'];
        foreach (self::WEEKDAYS as $day) {
            $row[$day] = (bool) $fields['Wizard' . ucfirst($day)];
        }
        $row['calendar'] = (int) $fields['WizardCalendar'];
        $row['dateFrom'] = (string) $fields['WizardDateFrom'];
        $row['dateTo'] = (string) $fields['WizardDateTo'];
        $row['condition'] = (string) $fields['WizardCondition'];
        $row['action'] = (int) $fields['WizardAction'];
        $row['actionCustom'] = ($row['action'] === self::ACTION_CUSTOM) ? (string) $fields['WizardActionCustom'] : self::ACTION_NONE;
        return $this->NormalizeSchedule($row);
    }

    /**
     * Assistant finished: appends the new entry to the timetable list and resets the assistant.
     *
     * @param string $value JSON with the assistant fields and the current list rows
     * @return void
     */
    private function OnWizardFinish(string $value): void
    {
        $data = json_decode($value, true);
        $list = is_array($data['list'] ?? null) ? $data['list'] : [];
        $list[] = $this->WizardRow($data['fields'] ?? null);
        $this->UpdateFormField('Timetable', 'values', json_encode(array_values($list)));
        $this->ApplyWizardTemplate(0);
    }

    /**
     * Describes a timetable entry in a human readable way.
     *
     * @param array<string,mixed> $schedule Timetable entry
     * @return array<int,array<string,string>> Summary rows with caption and value
     */
    private function ScheduleSummary(array $schedule): array
    {
        $schedule = $this->NormalizeSchedule($schedule);
        $trigger = (string) $schedule['trigger'];
        // When
        if ($trigger === self::TRIGGER_TIME) {
            $when = $this->FormatTime((string) $schedule['time']);
        } else {
            $when = $this->TriggerCaption($trigger);
            if ($trigger === self::TRIGGER_VARIABLE) {
                $when .= ' #' . (int) $schedule['triggerVariable'];
            }
            $offset = (int) $schedule['offset'];
            if ($this->IsSunTrigger($trigger) && ($offset !== 0)) {
                $when .= sprintf(' %+d min', $offset);
            }
            $window = ($trigger === self::TRIGGER_VARIABLE) ? ['windowStart', 'windowEnd'] : ['earliest', 'latest'];
            $earliest = $this->FormatTime((string) $schedule[$window[0]]);
            $latest = $this->FormatTime((string) $schedule[$window[1]]);
            if (($earliest !== '00:00') || ($latest !== '23:59')) {
                $when .= ' (' . $earliest . ' - ' . $latest;
                if ($this->IsSunTrigger($trigger) && $schedule['limitSkip']) {
                    $when .= ', ' . $this->Translate('otherwise no switching');
                }
                $when .= ')';
            }
        }
        if (((int) $schedule['random'] > 0) && ($trigger !== self::TRIGGER_VARIABLE)) {
            $when .= ' ± ' . (int) $schedule['random'] . ' min';
        }
        // Days
        $days = [];
        foreach (self::WEEKDAYS as $number => $day) {
            if ($schedule[$day]) {
                $days[] = $this->Translate(self::WEEKDAY_CAPTIONS[$number]);
            }
        }
        $days = empty($days) ? '-' : implode(', ', $days);
        $days .= ' | ' . $this->Translate(self::CALENDAR_CAPTIONS[(int) $schedule['calendar']] ?? '');
        if (($schedule['dateFrom'] !== '') || ($schedule['dateTo'] !== '')) {
            $days .= ' | ' . (($schedule['dateFrom'] !== '') ? $schedule['dateFrom'] : '01-01') . ' - ' . (($schedule['dateTo'] !== '') ? $schedule['dateTo'] : '12-31');
        }
        // Condition
        $condition = in_array((string) $schedule['condition'], ['', '[]'], true) ? $this->Translate('None') : $this->Translate('Yes');
        // Action
        $action = $this->Translate(self::ACTION_CAPTIONS[(int) $schedule['action']] ?? '');
        return [
            ['caption' => $this->Translate('Name'), 'value' => ($schedule['name'] !== '') ? $schedule['name'] : '-'],
            ['caption' => $this->Translate('When?'), 'value' => $when],
            ['caption' => $this->Translate('On which days?'), 'value' => $days],
            ['caption' => $this->Translate('Under which condition?'), 'value' => $condition],
            ['caption' => $this->Translate('What should happen?'), 'value' => $action],
        ];
    }

    /**
     * Determines the instance status from the current configuration.
     *
     * @return int Status code
     */
    private function CheckConfiguration(): int
    {
        if (!$this->ReadPropertyBoolean('InstanceActive')) {
            return IS_INACTIVE;
        }
        $schedules = $this->GetSchedules();
        $active = array_filter($schedules, fn (array $schedule): bool => (bool) $schedule['active']);
        if (empty($active)) {
            return self::STATUS_NO_SCHEDULE;
        }
        $devices = json_decode($this->ReadPropertyString('DeviceVariables'), true);
        $hasDevices = !empty($devices) || ($this->ReadPropertyInteger('DeviceScript') !== 0);
        $sun = false;
        foreach ($active as $schedule) {
            $custom = ((int) $schedule['action'] === self::ACTION_CUSTOM);
            if ($custom && !$this->HasCustomAction((string) $schedule['actionCustom'])) {
                return self::STATUS_NO_TARGET;
            }
            if (!$custom && !$hasDevices) {
                return self::STATUS_NO_TARGET;
            }
            if ($this->IsSunTrigger((string) $schedule['trigger'])) {
                $sun = true;
                if ($this->ResolveSunTrigger((string) $schedule['trigger']) === null) {
                    return self::STATUS_NO_LOCATION;
                }
            }
        }
        if ($sun && ($this->GetLocation() === null)) {
            return self::STATUS_NO_LOCATION;
        }
        if (!$this->CheckDevices()) {
            return self::STATUS_DEVICE;
        }
        if ($this->HasConflicts($active)) {
            return self::STATUS_CONFLICT;
        }
        return IS_ACTIVE;
    }

    /**
     * Checks for fixed time schedules which switch on and off at the same time on the same weekday.
     *
     * @param array<int,array<string,mixed>> $schedules Active timetable entries
     * @return bool True if a conflict was found
     */
    private function HasConflicts(array $schedules): bool
    {
        $slots = [];
        foreach ($schedules as $schedule) {
            $action = (int) $schedule['action'];
            if (($schedule['trigger'] !== self::TRIGGER_TIME) || ($action === self::ACTION_CUSTOM)) {
                continue;
            }
            $time = $this->FormatTime((string) $schedule['time']);
            foreach (self::WEEKDAYS as $day) {
                if (!$schedule[$day]) {
                    continue;
                }
                $key = $day . '|' . $time;
                if (isset($slots[$key]) && ($slots[$key] !== $action)) {
                    return true;
                }
                $slots[$key] = $action;
            }
        }
        return false;
    }

    /**
     * Returns all timetable entries, completed with default values.
     *
     * @return array<int,array<string,mixed>> List of schedules
     */
    private function GetSchedules(): array
    {
        $list = json_decode($this->ReadPropertyString('Timetable'), true);
        if (!is_array($list)) {
            return [];
        }
        $schedules = [];
        foreach ($list as $line) {
            if (is_array($line)) {
                $schedules[] = $this->NormalizeSchedule($line);
            }
        }
        return $schedules;
    }

    /**
     * Completes a timetable entry with default values and derives the effective trigger
     * from trigger type and sun event (entries without trigger type are converted).
     *
     * @param array<string,mixed> $line Timetable entry
     * @return array<string,mixed> Normalized timetable entry
     */
    private function NormalizeSchedule(array $line): array
    {
        $schedule = array_merge(self::SCHEDULE_DEFAULTS, $line);
        if (!isset($line['triggerType'])) {
            $trigger = (string) $schedule['trigger'];
            if (($trigger === self::TRIGGER_TIME) || ($trigger === self::TRIGGER_VARIABLE)) {
                $schedule['triggerType'] = $trigger;
            } else {
                $schedule['triggerType'] = self::TRIGGER_SUN_TYPE;
                $schedule['sunEvent'] = $trigger;
            }
            // The time window of a trigger variable used to be stored in earliest/latest
            if (($trigger === self::TRIGGER_VARIABLE) && !isset($line['windowStart'])) {
                $schedule['windowStart'] = $schedule['earliest'];
                $schedule['windowEnd'] = $schedule['latest'];
            }
        }
        $schedule['trigger'] = ($schedule['triggerType'] === self::TRIGGER_SUN_TYPE) ? (string) $schedule['sunEvent'] : (string) $schedule['triggerType'];
        return $schedule;
    }

    /**
     * A list entry has been added or edited: updates the derived trigger of all entries for the display.
     *
     * @param string $value JSON with all list rows
     * @return void
     */
    private function OnListChanged(string $value): void
    {
        $rows = json_decode($value, true);
        if (!is_array($rows)) {
            return;
        }
        $changed = false;
        foreach ($rows as &$row) {
            // Safety check: only complete rows are written back
            if (!is_array($row) || !array_key_exists('active', $row) || !array_key_exists('name', $row)) {
                $this->LogDebug(__FUNCTION__, 'Unexpected list data, nothing changed');
                return;
            }
            $normalized = $this->NormalizeSchedule($row);
            if (($row['trigger'] ?? null) !== $normalized['trigger']) {
                $row['trigger'] = $normalized['trigger'];
                $changed = true;
            }
        }
        unset($row);
        if ($changed) {
            $this->UpdateFormField('Timetable', 'values', json_encode(array_values($rows)));
        }
    }

    /**
     * Searches a form recursively (incl. popups, pages and radio button options) for an element by name.
     *
     * @param array<mixed> $node Form or part of it (modified in place)
     * @param string $name Name of the element
     * @param callable $callback Callback invoked with a reference to the matching element
     * @return bool True if the element was found
     */
    private function ModifyFormElementDeep(array &$node, string $name, callable $callback): bool
    {
        if (isset($node['name'], $node['type']) && ($node['name'] === $name)) {
            $callback($node);
            return true;
        }
        foreach ($node as &$child) {
            if (is_array($child) && $this->ModifyFormElementDeep($child, $name, $callback)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the display name of a schedule.
     *
     * @param array<string,mixed> $schedule Timetable entry
     * @param int $index Position in the timetable
     * @return string Name or position
     */
    private function ScheduleName(array $schedule, int $index): string
    {
        $name = trim((string) $schedule['name']);
        return ($name !== '') ? $name : '#' . ($index + 1);
    }

    /**
     * Returns the ID of the Location Control.
     *
     * @return int Instance ID or 0 if not available
     */
    private function GetLocationInstance(): int
    {
        $ids = IPS_GetInstanceListByModuleID(self::LOCATION_MODULE);
        return $ids[0] ?? 0;
    }

    /**
     * Returns the user defined offsets of the Location Control.
     *
     * @return array<string,array<string,mixed>> Offsets by ID with base trigger, offset (minutes) and name
     */
    private function GetLocationOffsets(): array
    {
        if ($this->offsetCache !== null) {
            return $this->offsetCache;
        }
        $this->offsetCache = [];
        $lc = $this->GetLocationInstance();
        if ($lc === 0) {
            return $this->offsetCache;
        }
        $offsets = json_decode((string) IPS_GetProperty($lc, 'Offsets'), true);
        foreach (is_array($offsets) ? $offsets : [] as $offset) {
            $id = (string) ($offset['ID'] ?? '');
            $base = self::LOCATION_BASES[(int) ($offset['Base'] ?? 0)] ?? null;
            if (($id === '') || ($base === null)) {
                continue;
            }
            $vid = @IPS_GetObjectIDByIdent($id, $lc);
            $this->offsetCache[$id] = [
                'base'   => $base,
                'offset' => (int) ($offset['Offset'] ?? 0),
                'name'   => ($vid !== false) ? IPS_GetName($vid) : $id,
            ];
        }
        return $this->offsetCache;
    }

    /**
     * Returns the select options for the user defined offsets of the Location Control.
     *
     * @return array<int,array<string,string>> Options with caption and value
     */
    private function TriggerOptions(): array
    {
        $options = [];
        foreach ($this->GetLocationOffsets() as $id => $offset) {
            $options[] = ['caption' => $offset['name'] . ' (Location Control)', 'value' => self::LOCATION_OFFSET . $id];
        }
        return $options;
    }

    /**
     * Returns the caption of a trigger.
     *
     * @param string $trigger Trigger
     * @return string Caption
     */
    private function TriggerCaption(string $trigger): string
    {
        if (str_starts_with($trigger, self::LOCATION_OFFSET)) {
            $offsets = $this->GetLocationOffsets();
            $id = substr($trigger, strlen(self::LOCATION_OFFSET));
            return isset($offsets[$id]) ? $offsets[$id]['name'] : $trigger;
        }
        return $this->Translate(self::TRIGGER_CAPTIONS[$trigger] ?? $trigger);
    }

    /**
     * Checks whether a trigger is based on a sun event (incl. Location Control offsets).
     *
     * @param string $trigger Trigger
     * @return bool True for sun events
     */
    private function IsSunTrigger(string $trigger): bool
    {
        return isset(self::TRIGGER_SUN[$trigger]) || str_starts_with($trigger, self::LOCATION_OFFSET);
    }

    /**
     * Resolves a sun trigger into the key of date_sun_info() and an additional offset.
     *
     * @param string $trigger Trigger
     * @return array<string,mixed>|null ['key' => string, 'offset' => seconds] or null if not resolvable
     */
    private function ResolveSunTrigger(string $trigger): ?array
    {
        if (isset(self::TRIGGER_SUN[$trigger])) {
            return ['key' => self::TRIGGER_SUN[$trigger], 'offset' => 0];
        }
        if (str_starts_with($trigger, self::LOCATION_OFFSET)) {
            $offset = $this->GetLocationOffsets()[substr($trigger, strlen(self::LOCATION_OFFSET))] ?? null;
            if ($offset !== null) {
                return ['key' => self::TRIGGER_SUN[$offset['base']], 'offset' => $offset['offset'] * 60];
            }
        }
        return null;
    }

    /**
     * Returns the variables of all active schedules with a variable trigger.
     *
     * @return array<int,int> Variable IDs
     */
    private function GetTriggerVariables(): array
    {
        $ids = [];
        foreach ($this->GetSchedules() as $schedule) {
            $vid = (int) $schedule['triggerVariable'];
            if ($schedule['active'] && ($schedule['trigger'] === self::TRIGGER_VARIABLE) && ($vid > 0)) {
                $ids[] = $vid;
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * Returns the device variables.
     *
     * @return array<int,int> Variable IDs
     */
    private function GetDeviceVariables(): array
    {
        $ids = [];
        $variables = json_decode($this->ReadPropertyString('DeviceVariables'), true);
        foreach (is_array($variables) ? $variables : [] as $variable) {
            $vid = (int) ($variable['VariableID'] ?? 0);
            if ($vid > 0) {
                $ids[] = $vid;
            }
        }
        return array_values(array_unique($ids));
    }

    /**
     * Returns all objects used by the configuration (devices, script, calendar, triggers, conditions and actions).
     *
     * @return array<int,int> Object IDs
     */
    private function GetReferencedObjects(): array
    {
        $ids = array_merge($this->GetDeviceVariables(), $this->GetTriggerVariables());
        foreach (['DeviceScript', 'CalendarHoliday', 'CalendarVacation', 'CalendarLock'] as $property) {
            $ids[] = $this->ReadPropertyInteger($property);
        }
        // Variables of conditions and targets of individual actions
        $collect = function (mixed $node) use (&$collect, &$ids): void
        {
            if (!is_array($node)) {
                return;
            }
            foreach ($node as $key => $value) {
                if (in_array($key, ['variableID', 'TARGET'], true) && is_numeric($value)) {
                    $ids[] = (int) $value;
                } else {
                    $collect($value);
                }
            }
        };
        foreach ($this->GetSchedules() as $schedule) {
            $collect(json_decode((string) $schedule['condition'], true));
            $collect(json_decode((string) $schedule['actionCustom'], true));
        }
        return array_values(array_unique(array_filter($ids, fn (int $id): bool => ($id >= 10000) && IPS_ObjectExists($id))));
    }

    /**
     * Checks whether a device variable can be switched (action or, if allowed, direct value for variables without instance).
     *
     * @param int $vid Variable ID
     * @return bool True if switchable
     */
    private function IsSwitchable(int $vid): bool
    {
        if (!IPS_VariableExists($vid) || (IPS_GetVariable($vid)['VariableType'] !== VARIABLETYPE_BOOLEAN)) {
            return false;
        }
        return HasAction($vid) || $this->IsDirectSwitchable($vid);
    }

    /**
     * Checks whether a variable without action may be set directly.
     * Only allowed by the user and only for variables which do not belong to an instance.
     *
     * @param int $vid Variable ID
     * @return bool True if the value may be set directly
     */
    private function IsDirectSwitchable(int $vid): bool
    {
        return $this->ReadPropertyBoolean('SettingsDirect') && !IPS_InstanceExists(IPS_GetParent($vid));
    }

    /**
     * Checks all device variables and the device script.
     *
     * @return bool True if all devices are switchable
     */
    private function CheckDevices(): bool
    {
        foreach ($this->GetDeviceVariables() as $vid) {
            if (!$this->IsSwitchable($vid)) {
                $this->LogDebug(__FUNCTION__, 'Device #' . $vid . ' missing or not switchable');
                return false;
            }
        }
        $script = $this->ReadPropertyInteger('DeviceScript');
        if (($script !== 0) && !IPS_ScriptExists($script)) {
            $this->LogDebug(__FUNCTION__, 'Script #' . $script . ' missing');
            return false;
        }
        return true;
    }

    /**
     * A device variable has changed: synchronizes the switch variable and detects manual switching.
     *
     * @param int $vid Variable ID
     * @param bool $value New value
     * @return void
     */
    private function OnDeviceUpdate(int $vid, bool $value): void
    {
        $expected = json_decode($this->GetBuffer('Expected'), true);
        $expected = is_array($expected) ? $expected : [];
        $own = isset($expected[$vid]) && ($expected[$vid]['state'] === $value) && ((time() - $expected[$vid]['time']) <= self::FEEDBACK_WINDOW);
        unset($expected[$vid]);
        $this->SetBuffer('Expected', json_encode($expected));
        if (!$own) {
            $this->LogDebug(__FUNCTION__, 'Device #' . $vid . ' switched manually: ' . var_export($value, true));
            $this->StartOverride();
        }
        if ($this->ReadPropertyBoolean('SettingsSwitch')) {
            $state = false;
            foreach ($this->GetDeviceVariables() as $device) {
                $state = $state || (IPS_VariableExists($device) && (bool) GetValue($device));
            }
            $this->SetValueBoolean('switch_proxy', $state);
        }
    }

    /**
     * Reads the geographic position from the Location Control.
     *
     * @return array<string,float>|null Latitude and longitude or null if not available
     */
    private function GetLocation(): ?array
    {
        $lc = $this->GetLocationInstance();
        if ($lc === 0) {
            return null;
        }
        $location = json_decode((string) IPS_GetProperty($lc, 'Location'), true);
        if (!is_array($location) || !isset($location['latitude'], $location['longitude'])) {
            return null;
        }
        return ['latitude' => (float) $location['latitude'], 'longitude' => (float) $location['longitude']];
    }

    /**
     * Reads a boolean variable.
     *
     * @param int $vid Variable ID
     * @return bool Value or false if the variable does not exist
     */
    private function ReadBoolVariable(int $vid): bool
    {
        return IPS_VariableExists($vid) && (bool) GetValue($vid);
    }

    /**
     * Returns the start of the day of a timestamp.
     *
     * @param int $ts Timestamp
     * @return int Timestamp of 00:00:00
     */
    private function DayStart(int $ts): int
    {
        return mktime(0, 0, 0, (int) date('n', $ts), (int) date('j', $ts), (int) date('Y', $ts));
    }

    /**
     * Returns the start of the following day (daylight saving time safe).
     *
     * @param int $day Start of the day (timestamp)
     * @return int Timestamp of 00:00:00 of the next day
     */
    private function NextDay(int $day): int
    {
        return mktime(0, 0, 0, (int) date('n', $day), (int) date('j', $day) + 1, (int) date('Y', $day));
    }

    /**
     * Combines a day with a time of day (SelectTime format).
     *
     * @param int $day Start of the day (timestamp)
     * @param string $time Time as SelectTime JSON
     * @return int Timestamp
     */
    private function DayTime(int $day, string $time): int
    {
        $value = json_decode($time, true);
        $hour = max(0, (int) ($value['hour'] ?? 0));
        $minute = max(0, (int) ($value['minute'] ?? 0));
        $second = max(0, (int) ($value['second'] ?? 0));
        return mktime($hour, $minute, $second, (int) date('n', $day), (int) date('j', $day), (int) date('Y', $day));
    }

    /**
     * Formats a time of day (SelectTime format) as HH:MM.
     *
     * @param string $time Time as SelectTime JSON
     * @return string Formatted time
     */
    private function FormatTime(string $time): string
    {
        $value = json_decode($time, true);
        return sprintf('%02d:%02d', max(0, (int) ($value['hour'] ?? 0)), max(0, (int) ($value['minute'] ?? 0)));
    }

    /**
     * Converts a v2 timetable entry into the v3 format.
     *
     * @param array<string,mixed> $line v2 entry
     * @param int $trigger Former global external trigger variable
     * @return array<string,mixed> v3 entry
     */
    private function MigrateScheduleV2(array $line, int $trigger): array
    {
        $schedule = $line['schedule'];
        if (is_string($schedule)) {
            $schedule = json_decode($schedule, true);
        }
        if (!is_array($schedule)) {
            $schedule = [];
        }
        $event = (string) ($schedule['event'] ?? 'Off');
        if ($event === 'Off') {
            $event = self::TRIGGER_TIME;
        } elseif ($event === 'ExternalTrigger') {
            $event = self::TRIGGER_VARIABLE;
        }
        $entry = self::SCHEDULE_DEFAULTS;
        foreach (self::WEEKDAYS as $day) {
            $entry[$day] = (bool) ($line[$day] ?? true);
        }
        $entry['active'] = ((int) ($line['status'] ?? 2) === 1);
        $entry['name'] = isset($line['id']) ? '#' . $line['id'] : '';
        $entry['trigger'] = $event;
        $entry['time'] = ($event === self::TRIGGER_TIME) ? $this->ConvertTimeV2((string) ($line['time'] ?? ''), self::TIME_START) : self::TIME_START;
        $entry['action'] = (bool) ($line['action'] ?? true) ? self::ACTION_ON : self::ACTION_OFF;
        $entry['calendar'] = (int) ($line['rule'] ?? 0);
        $entry['offset'] = (int) ($schedule['offset'] ?? 0);
        $entry['earliest'] = $this->ConvertTimeV2((string) ($schedule['earliest'] ?? ''), self::TIME_START);
        $entry['latest'] = $this->ConvertTimeV2((string) ($schedule['latest'] ?? ''), self::TIME_END);
        $entry['triggerVariable'] = ($event === self::TRIGGER_VARIABLE) ? $trigger : 0;
        $entry['condition'] = (string) ($line['condition'] ?? '[]');
        // Derive trigger type, sun event and time window from the v2 trigger
        unset($entry['triggerType'], $entry['windowStart'], $entry['windowEnd']);
        return $this->NormalizeSchedule($entry);
    }

    /**
     * Converts a v2 time string (HH:MM:SS or --:--:--) into the SelectTime format.
     *
     * @param string $value v2 time string
     * @param string $default Value for an unset time
     * @return string Time as SelectTime JSON
     */
    private function ConvertTimeV2(string $value, string $default): string
    {
        if (preg_match('/^(\d{2}):(\d{2}):(\d{2})$/', $value, $match)) {
            return json_encode(['hour' => (int) $match[1], 'minute' => (int) $match[2], 'second' => (int) $match[3]]);
        }
        return $default;
    }

    /**
     * Checks whether an individual action (SelectAction format) is selected.
     *
     * @param string $value Action as JSON
     * @return bool True if an action is selected
     */
    private function HasCustomAction(string $value): bool
    {
        $action = json_decode($value, true);
        return is_array($action) && !empty($action['actionID']);
    }

    /**
     * Shows a message popup in the configuration form.
     *
     * @param string $caption Message (will be translated)
     * @return void
     */
    private function EchoMessage(string $caption): void
    {
        $this->UpdateFormField('EchoMessage', 'caption', $this->Translate($caption));
        $this->UpdateFormField('EchoPopup', 'visible', true);
    }

    /**
     * Switch Variable/Script
     *
     * @param bool $state ON/OFF.
     * @return bool True if successful, false otherwise
     */
    private function SwitchDevice(bool $state): bool
    {
        $this->LogDebug(__FUNCTION__, 'New State: ' . var_export($state, true));
        // Check Script
        $ds = $this->ReadPropertyInteger('DeviceScript');
        if ($ds !== 0) {
            if (IPS_ScriptExists($ds)) {
                $rs = IPS_RunScriptEx($ds, ['State' => $state]);
                $this->LogDebug(__FUNCTION__, 'RunScript: ' . var_export($rs, true));
            } else {
                $this->LogDebug(__FUNCTION__, 'Script #' . $ds . ' does not exist!');
            }
        }
        // Check Variables
        $expected = json_decode($this->GetBuffer('Expected'), true);
        $expected = is_array($expected) ? $expected : [];
        $ret = true;
        foreach ($this->GetDeviceVariables() as $vid) {
            // Remember the state, so the feedback of the device is not regarded as manual switching
            $expected[$vid] = ['state' => $state, 'time' => time()];
            if (IPS_VariableExists($vid) && HasAction($vid)) {
                $ok = (@RequestAction($vid, $state) !== false);
            } elseif (IPS_VariableExists($vid) && $this->IsDirectSwitchable($vid)) {
                $ok = SetValueBoolean($vid, $state);
            } else {
                $ok = false;
            }
            if (!$ok) {
                $this->LogDebug(__FUNCTION__, 'Device #' . $vid . ' could not be switched!');
                unset($expected[$vid]);
                $ret = false;
            }
        }
        $this->SetBuffer('Expected', json_encode($expected));
        if ($ret === false) {
            $this->LogMessage('One or more devices could not be switched!', KL_WARNING);
        }
        return $ret;
    }
}
