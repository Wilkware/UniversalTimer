# ⏲️ Universelle Zeitschaltuhr (Universal Timer)

[![Home](https://img.shields.io/badge/Home-wilkware.de-0b1830.svg?style=flat-square)](https://wilkware.de/module/universal-zeitschaltuhr/)
[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-9.1-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-3.0.20261015-orange.svg?style=flat-square)](https://github.com/Wilkware/UniversalTimer)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg?style=flat-square)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![Actions](https://img.shields.io/github/actions/workflow/status/wilkware/UniversalTimer/ci.yml?branch=main&label=CI&style=flat-square)](https://github.com/Wilkware/UniversalTimer/actions)

Dieses Modul ermöglicht gezielte Schaltvorgänge zu bestimmten Uhrzeiten oder in Abhängigkeit von Ereignissen.

## Inhaltverzeichnis

1. [Funktionsumfang](#user-content-1-funktionsumfang)
2. [Voraussetzungen](#user-content-2-voraussetzungen)
3. [Installation](#user-content-3-installation)
4. [Einrichtung](#user-content-4-einrichtung)
5. [Statusvariablen](#user-content-5-statusvariablen)
6. [Darstellungen](#user-content-6-darstellungen)
7. [Visualisierung](#user-content-7-visualisierung)
8. [Befehlsreferenz](#user-content-8-befehlsreferenz)
9. [Versionshistorie](#user-content-9-versionshistorie)

### 1. Funktionsumfang

Für eine einfache Zeitschaltung wäre dieses Modul normalerweise nicht notwendig, ein Wochenplan oder ein zyklisches Ereignis ist mit den Bordmitteln von Symcon schnell erstellt.
Interessant wird es erst, wenn man zeitgesteuertes und bedingtes Schalten kombinieren möchte, z.B. ...

* Rollläden/Jalousien an Arbeitstagen zum Sonnenaufgang hochfahren, aber frühestens um 06:30 Uhr und nicht an Feiertagen
* Außenbeleuchtung zur Dämmerung einschalten und pünktlich um Mitternacht wieder ausschalten
* Notlicht an der Haustür nur einschalten, wenn jemand zu Hause ist (Bedingung)
* Weihnachtsbeleuchtung nur im Zeitraum vom 1. Dezember bis 6. Januar schalten
* Anwesenheitssimulation mit zufälliger Abweichung der Schaltzeiten

Funktionen im Überblick:

* Beliebig viele Zeitpläne pro Instanz, jeder mit eigenem Auslöser, eigenen Tagen, eigener Bedingung und eigener Aktion
* Auslöser: feste Uhrzeit, 8 Sonnenereignisse (Sonnenauf- und -untergang sowie zivile, nautische und astronomische Dämmerung), im Location Control hinterlegte Offsets oder Wechsel einer Variable auf TRUE
* Versatz zum Sonnenereignis sowie frühester und spätester Schaltzeitpunkt; außerhalb der Grenzen wird wahlweise auf die Grenze verschoben oder an diesem Tag nicht geschaltet (z.B. kein Einschalten, wenn das Ausschalten schon vorher erfolgte)
* Zufällige Abweichung (± Minuten), stabil pro Tag und Zeitplan
* Wochentage, Kalenderregeln (Feiertage/Ferien) und Gültigkeitszeitraum (auch über den Jahreswechsel)
* Bedingungen über die Symcon-Bedingungsauswahl, geprüft zur Schaltzeit
* Aktionen: Einschalten, Ausschalten (Geräteliste und/oder Skript) oder eine beliebige individuelle Symcon-Aktion (z.B. Dimmwert, Rollladenposition, Szene)
* Geführte Erstellung neuer Zeitpläne über einen Assistenten mit Vorlagen
* Vorschau aller Schaltpunkte der nächsten 7 Tage
* Abgleich der Schaltervariable mit dem Zustand der Geräte, auch bei direktem Schalten der Geräte
* Optional: Soll-Zustand nach einem Neustart herstellen, Pausieren der Automatik bzw. Überspringen des nächsten Schaltpunkts nach manuellem Schalten, globale Sperre über eine Variable
* Optional: Variablen ohne Aktion (z.B. eigene Hilfsvariablen) direkt schalten

---

#### Wie erstellt man einen Zeitplan?

Am einfachsten über den Schalter **ASSISTENT** unter der Zeitplan-Liste (Panel "Zeitsteuerung ...").
Der Assistent führt Schritt für Schritt durch Vorlage und Name, Auslöser, Tage, Bedingung und Aktion und zeigt am Ende eine Zusammenfassung.
Mit OK wird der Zeitplan der Liste hinzugefügt, danach müssen die Änderungen noch übernommen werden.

Alternativ legt **HINZUFÜGEN** direkt einen Eintrag über den Bearbeiten-Dialog an.

---

#### Wie bearbeitet man einen Zeitplan?

Über das Bearbeiten-Symbol eines Eintrags öffnet sich ein Dialog. Auslöser (Feste Uhrzeit, Sonnenereignis, Auslöservariable) und Aktion werden über Optionsfelder gewählt,
die zugehörigen Einstellungen erscheinen jeweils direkt unter der gewählten Option.
Aktiv und die Wochentage können direkt in der Liste umgeschaltet werden, die Reihenfolge lässt sich per Drag & Drop ändern.

---

#### Was passiert, wenn mehrere Zeitpläne gleichzeitig fällig sind?

Alle fälligen Zeitpläne werden in der Reihenfolge der Liste ausgeführt, der unterste Eintrag gewinnt also.
Schalten zwei Zeitpläne mit fester Uhrzeit zur gleichen Zeit am gleichen Wochentag gegensätzlich, wird das über den Instanzstatus gemeldet.

---

#### Wie werden Feiertage und Ferien berücksichtigt?

Im Panel "Kalender ..." werden boolesche Variablen hinterlegt, die für den aktuellen Tag angeben, ob ein Feiertag bzw. Ferien sind (z.B. aus dem Modul [Almanach](https://github.com/Wilkware/Almanac)).
Die Kalenderregel eines Zeitplans wird zur Schaltzeit gegen diese Variablen geprüft.

### 2. Voraussetzungen

* Symcon ab Version 9.1
* Für Sonnenereignisse: eingerichtetes Location Control (Standort)

### 3. Installation

* Über den Modul Store das Modul _Universelle Zeitschaltuhr_ installieren.
* Alternativ über das Modul Control folgende URL hinzufügen.  
`https://github.com/Wilkware/UniversalTimer` oder `git://github.com/Wilkware/UniversalTimer.git`

### 4. Einrichtung

* Unter 'Instanz hinzufügen' ist das _Universelle Zeitschaltuhr_-Modul unter dem Hersteller '(Geräte)' aufgeführt.

__Konfigurationsseite__:

Einstellungsbereich:

> 🎚️ Schaltung ...

Name                            | Beschreibung
------------------------------- | -----------------------------------------------------------------
An / Aus                        | Aktiviert bzw. deaktiviert die gesamte Zeitschaltuhr

> ⏱️ Zeitsteuerung ...

Name                            | Beschreibung
------------------------------- | -----------------------------------------------------------------
Zeitplan                        | Liste aller Zeitpläne (Aktiv, Name, Wochentage, Auslöser, Zeit, Regel, Aktion)
ASSISTENT                       | Geführte Erstellung eines neuen Zeitplans mit Vorlagen

Einstellungen eines Zeitplans (Bearbeiten-Dialog bzw. Assistent):

Name                            | Beschreibung
------------------------------- | -----------------------------------------------------------------
Name                            | Frei wählbare Bezeichnung (ohne Namen wird die Position '#n' angezeigt)
Aktiv                           | Zeitplan aktiv bzw. inaktiv
Auslöser                        | Feste Uhrzeit, Sonnenereignis oder Auslöservariable
Uhrzeit                         | Schaltzeitpunkt (Feste Uhrzeit)
Ereignis                        | Eines der 8 Sonnenereignisse oder ein im Location Control hinterlegter Offset (Sonnenereignis)
Versatz (Minuten)               | Verschiebung zum Ereignis, auch negativ; bei Offsets des Location Control zusätzlich zu deren Versatz (Sonnenereignis)
Frühestens / Spätestens         | Begrenzung des Schaltzeitpunkts (Sonnenereignis) bzw. Zeitfenster, in dem die Auslöservariable schaltet (Auslöservariable); 00:00 bzw. 23:59 = keine Grenze
Außerhalb nicht schalten        | Liegt der Schaltzeitpunkt außerhalb der Grenzen, wird an diesem Tag nicht geschaltet statt auf die Grenze zu verschieben (Sonnenereignis)
Auslöservariable                | Boolesche Variable, deren Wechsel auf TRUE schaltet (Auslöservariable)
Zufällige Abweichung            | Zufällige Verschiebung um bis zu ± n Minuten (nicht bei Auslöservariable)
MO - SO                         | Wochentage, an denen geschaltet wird
Regel                           | Kalenderregel: Immer, an/nicht an Feiertagen, an/nicht an Ferientagen, an/nicht an Feiertagen oder Ferientagen
Gültig ab / bis (MM-TT)         | Gültigkeitszeitraum, leer = ganzjährig (über den Jahreswechsel möglich, z.B. 12-01 bis 01-06)
Bedingung                       | Bedingung, die zur Schaltzeit erfüllt sein muss
Aktion                          | Einschalten, Ausschalten oder Individuelle Aktion
Individuelle Aktion             | Beliebige Symcon-Aktion (nur bei Aktion Individuelle Aktion)

> 📅 Kalender ...

Name                            | Beschreibung
------------------------------- | -----------------------------------------------------------------
Heute Feiertag                  | Boolesche Variable, die anzeigt, ob heute ein Feiertag ist
Heute Ferien                    | Boolesche Variable, die anzeigt, ob heute Ferien sind
Sperre                          | Boolesche Variable; solange sie TRUE ist, wird nicht geschaltet

> 💡 Geräte ...

Name                            | Beschreibung
------------------------------- | -----------------------------------------------------------------
Schaltvariablen                 | Liste der zu schaltenden Variablen (Boolean), geschaltet per RequestAction; Änderungen der Geräte werden überwacht und in die Statusvariable 'Schalter' übernommen
Gleichzeitiges Ausführen eines Skriptes | Skript, welches zusätzlich ausgeführt wird (IPS_RunScriptEx); der Zustand wird im Array als 'State' (true/false) übergeben

_Hinweis:_ Geräte und Skript werden von den Aktionen Einschalten und Ausschalten verwendet.

> ⚙️ Erweiterte Einstellungen ...

Name                            | Beschreibung
------------------------------- | -----------------------------------------------------------------
Statusvariable anlegen          | Legt die Statusvariable 'Schalter' an, über die die Geräte manuell geschaltet werden (z.B. in der Visualisierung); sie zeigt zugleich den Zustand der Geräte (An, sobald mindestens ein Gerät an ist)
Soll-Zustand herstellen         | Ermittelt nach einem Neustart bzw. beim Übernehmen der Konfiguration den letzten gültigen Schaltpunkt der letzten 24 Stunden und schaltet die Geräte nur, wenn ihr Zustand davon abweicht. Individuelle Aktionen und Skripte ohne Schaltvariablen werden nur nach einem Neustart nachgeholt (standardmäßig aktiv)
Nach manuellem Schalten         | Verhalten nach manuellem Schalten (über die Statusvariable 'Schalter' oder direkt am Gerät): Automatik unverändert fortsetzen; Auslöservariablen und Nachholen bis zum nächsten Schaltpunkt pausieren; zusätzlich den nächsten Schaltpunkt überspringen (z.B. bleibt ein von Hand ausgeschaltetes Licht aus, auch wenn der nächste Zeitplan es einschalten würde)
Variablen ohne Aktion direkt setzen | Schaltvariablen ohne Aktion werden direkt gesetzt (SetValue); nur für Variablen, die zu keiner Instanz gehören

Aktionsbereich:

Name                            | Beschreibung
------------------------------- | -----------------------------------------------------------------
Jetzt einschalten               | Schaltet die Geräte (und das Skript) sofort ein, ohne Prüfung von Zeitplänen und Bedingungen
Jetzt ausschalten               | Schaltet die Geräte (und das Skript) sofort aus, ohne Prüfung von Zeitplänen und Bedingungen
Vorschau nächste 7 Tage         | Zeigt alle berechneten Schaltpunkte der nächsten 7 Tage; Kalenderregeln und Bedingungen werden erst zur Schaltzeit geprüft und daher nur als Hinweis angezeigt

_Hinweis:_ Fehlt eine Schaltvariable bzw. das Skript oder ist eine Schaltvariable nicht schaltbar, wird das über den Instanzstatus gemeldet.

_Hinweis:_ Zeitpläne aus Version 2.x werden automatisch in das neue Format übernommen, ein bisher hinterlegter externer Auslöser wird den betreffenden Zeitplänen als Auslöservariable zugeordnet.

### 5. Statusvariablen

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

Name                 | Typ     | Beschreibung
-------------------- | ------- | ------------------------------------------------------------
Schalter             | Boolean | Zustand der Geräte und manuelles Schalten (nur mit 'Statusvariable anlegen')
Automatik            | Boolean | Schaltet die Automatik ein/aus; bei Aus wird weiter berechnet, aber nicht geschaltet
Nächste Schaltung    | Integer | Zeitpunkt der nächsten Schaltung
Nächste Aktion       | String  | Aktion und Name des nächsten Zeitplans
Letzte Schaltung     | Integer | Zeitpunkt der letzten ausgeführten Schaltung

### 6. Darstellungen

Die Darstellungen werden direkt an den Statusvariablen hinterlegt, es werden keine Profile angelegt.

Variable             | Darstellung   | Werte
-------------------- | ------------- | ------------------------------------------------------------
Schalter             | Schalter      | An / Aus
Automatik            | Schalter      | An / Aus
Nächste Schaltung    | Datum/Uhrzeit | Wochentag, Tag, Monat, Stunden und Minuten
Nächste Aktion       | Wertanzeige   | Text
Letzte Schaltung     | Datum/Uhrzeit | Wochentag, Tag, Monat, Stunden und Minuten

### 7. Visualisierung

Die Statusvariablen können direkt in der Visualisierung verwendet werden.

### 8. Befehlsreferenz

```php
string UTSC_GetScheduleForm(int $InstanzID);
```

Liefert den Bearbeiten-Dialog eines Zeitplans für die Konfigurationsseite.  
_Hinweis:_ Die Funktion wird intern vom Konfigurationsformular verwendet, ein direkter Aufruf ist nicht notwendig!

### 9. Versionshistorie

v3.0.20261015

* _NEU_: Kompatibilität auf Symcon 9.1 hoch gesetzt
* _NEU_: Auswertung der Zeitpläne und Schalten der Geräte (bisher reine Konzeptstudie)
* _NEU_: Sonnenereignisse mit Versatz sowie frühestem und spätestem Schaltzeitpunkt (verschieben oder nicht schalten)
* _NEU_: Offsets des Location Control als Auslöser
* _NEU_: Auslöservariable pro Zeitplan (ersetzt den globalen externen Auslöser)
* _NEU_: Zufällige Abweichung, Gültigkeitszeitraum und Kalenderregeln (Feiertage/Ferien über frei wählbare Variablen)
* _NEU_: Individuelle Aktion pro Zeitplan
* _NEU_: Assistent mit Vorlagen zur geführten Erstellung von Zeitplänen
* _NEU_: Bearbeiten-Dialog und Assistent mit Optionsfeldern für Auslöser und Aktion, es werden nur die relevanten Einstellungen gezeigt
* _NEU_: Vorschau der Schaltpunkte der nächsten 7 Tage
* _NEU_: Statusvariablen Automatik, Nächste Schaltung, Nächste Aktion und Letzte Schaltung
* _NEU_: Soll-Zustand nach Neustart bzw. Übernehmen herstellen (nur bei Abweichung), Pausieren nach manuellem Schalten und globale Sperre
* _NEU_: Schaltervariable folgt dem Zustand der Geräte, manuelles Schalten am Gerät wird erkannt
* _NEU_: Optional direktes Setzen von Variablen ohne Aktion
* _NEU_: Prüfung der Schaltvariablen und Referenzen auf alle verwendeten Objekte
* _NEU_: Darstellungen statt Profilen
* _NEU_: Bestehende Zeitpläne werden automatisch übernommen

v2.0.20260201

* _NEU_: Projektumstrukturierung hin zu einer globalen CI/CD-Pipeline
* _NEU_: Kompatibilität auf Symcon 8.1 hoch gesetzt
* _NEU_: Umstellung auf IPSModuleStrict
* _NEU_: Kompatibilität für Symcon 8.2 vorbereitet
* _NEU_: Modulversion wird in Quellcodesektion angezeigt
* _FIX_: Bibliotheksfunktionen angeglichen

v1.0.20220228

* _NEU_: Initialversion

## Entwickler

Seit nunmehr über 10 Jahren fasziniert mich das Thema Haussteuerung. In den letzten Jahren betätige ich mich auch intensiv in der Symcon Community und steuere dort verschiedenste Skript und Module bei. Ihr findet mich dort unter dem Namen @pitti ;-)

[![GitHub](https://img.shields.io/badge/GitHub-@wilkware-181717.svg?style=for-the-badge&logo=github)](https://wilkware.github.io/)

## Spenden

Die Software ist für die nicht kommerzielle Nutzung kostenlos, über eine Spende bei Gefallen des Moduls würde ich mich freuen.

[![PayPal](https://img.shields.io/badge/PayPal-spenden-00457C.svg?style=for-the-badge&logo=paypal)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=8816166)

## Lizenz

Namensnennung - Nicht-kommerziell - Weitergabe unter gleichen Bedingungen 4.0 International

[![Licence](https://img.shields.io/badge/License-CC_BY--NC--SA_4.0-EF9421.svg?style=for-the-badge&logo=creativecommons)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
