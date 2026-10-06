# ⏲️ Universelle Zeitschaltuhr (Universal Timer)

[![Version](https://img.shields.io/badge/Symcon-PHP--Modul-red.svg?style=flat-square)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Product](https://img.shields.io/badge/Symcon%20Version-8.1-blue.svg?style=flat-square)](https://www.symcon.de/produkt/)
[![Version](https://img.shields.io/badge/Modul%20Version-2.0.20220228-orange.svg?style=flat-square)](https://github.com/Wilkware/UniversalTimer)
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

Für eine einfache Zeitschaltung wäre dieses Modul normalerweise nicht notwendig. Die Erstellung einen Wochenplanes oder eines zyklischen Ereignisses ist mit IPS Bordmitteln recht einfach möglich. Interessant wird die Sache erst wenn man bedingtes und zyklisches Schalten verbinden möchte.
Für eine solche Kombination gibt es eine Reihe von Anwendungsfälle, wie z.B. ...

* Rollläden/Jalousien am Morgen zu einer definierten Zeit hochfahren (Arbeitstag unabhängig von Jahreszeit), aber abends zum Sonnenuntergang runterfahren
* Außenbeleuchtung bei einsetzender Dunkelheit einschalten, aber pünktlich um Mitternacht wieder ausschalten
* Haustür Notlicht bei einsetzen der Dämmerung Ein- bzw.- Ausschalten
* oder zur Weihnachtszeit die Beleuchtung situativ schalten.

Das nur um einige Anregungen zu geben. Wahrscheinlich gibt es da noch einiges mehr an Ideen, welche sich so umsetzen lassen.

Mit diesem Modul kann man mehrere Zeitpläne anlegen und so ein hohes Maß an Flexibilität erreichen, z.B. an verschiedenen Wochentagen unterschiedliche Zeiten verwenden oder Feriertage und Schulferien unterschiedlich berücksichtigen.

---

#### Wie aktiviert bzw. deaktiviert man die Zeitschaltuhr?

Um alle Zeitpläne (unabhängig von ihrem eigenen Status) einer Zeitschaltuhr global zu aktivieren bzw. deaktivieren
nutzt man den Schalter im Panel "Schaltung ...". Dieser schaltet die Instanz ensprechend aktiv oder inaktiv.

---

#### Wie erstellt man einen Zeitplan?

Dies ist die komplexeste Aufgabe innerhalb der Modulkonfiguration und befindet sich im Panel "Zeitsteuerung ...".
Über den Schalter HINZUFÜGEN wird ein neuer Zeitplan in der Zeitplan-Liste angelegt.

---

#### Wie bearbeitet man einen Zeitplan?

Um einen Zeitplan zu bearbeiten und anzupassen muss dieser zuerst markiert werden (grau hinterlegt). Dies erfolgt durch einfachen Mausklick auf die erste Spalte (Selektor ≡) der entsprechenden Zeile in der Liste.  
_ACHTUNG:_ nur der Klick auf '≡' führt hier zum Ziel!!!  
Danach werden die aktuellen Werte eines Zeitplans unterhalb der Schalterreihe angezeigt!
Hat man dann seine Anpassungen in den einzelnen Sektionen getätigt, speichert man über den Schalter AKTUALISIEREN die Änderungen zurück in die Liste (immer noch grau selektierter Listeneintrag).

---

#### Wie löscht man einen Zeitplan?

Einfach auf das Mülleimersymbol am Ende eines jeden Listeneintrags drücken.  
_ACHTUNG:_ Es erfolgt keine Nachfrage, alle Daten gehen sofort verloren!

---

#### Wie kann ich schnell einen Zeitplan anlegen mit leichten Änderungen zu einem anderen Zeitplan?

Einfach einen Zeitplan auswählen (klick auf auf ≡ Zelle) und dann den Schalter DUPLIZIEREN drücken.
Dannach kann man den neu erzeugten Plan wieder markieren (≡) und ändern.

---

#### Wie kann ich Zeitpläne sortieren?

Mittels Drag&Drop via der Selektionsspalte (≡) kann die Reihenfolge der Zeitpläne innerhalb der Liste verändert werden.
Wenn man die richtige Reihenfolge festgelegt hat, erfolgt durch Drücken des Schalters NEUSORTIERUNG die Umbennenung der IDs (Spalte #).

---

#### Wie kann ich einen externen Auslöser festlegen?

Im Panel "Erweiterte Einstellungen ..." kann man eine Variable vom Typ Boolean als Auslöser hinterlegen.

### 2. Voraussetzungen

* Symcon ab Version 8.1

### 3. Installation

* Über den Modul Store das Modul _Universelle Zeitschaltuhr_ installieren.
* Alternativ über das Modul Control folgende URL hinzufügen.  
`https://github.com/Wilkware/UniversalTimer` oder `git://github.com/Wilkware/UniversalTimer.git`

### 4. Einrichtung

* Unter 'Instanz hinzufügen' ist das _Universelle Zeitschaltuhr_-Modul unter dem Hersteller '(Geräte)' aufgeführt.

__Konfigurationsseite__:

Einstellungsbereich:

> 🎛️ Schaltung ...

Name                  | Beschreibung
--------------------- | ---------------------------------
An /Aus               | Schalter zum Aktivieren bzw. Deaktivieren der gesamten Schaltuhr

> ⏱️ Zeitsteuerung ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Zeitplan              | Liste, welche alle Zeitpläne speichert
HINZUFÜGEN            | Fügt einen neuen Zeitplan zur Liste hinzu
DUPLIZIEREN           | Dupliziert den aktuell ausgewählten Plan (grau markiert) in der Liste (inklusive der Einstellungen)
NEUSORTIEREN          | Nummeriert die Pläne von 1 bis N entsprechend der aktuellen Reihgenfolge in der Liste
AKTUALISIEREN         | Speichert alle Ändererungen an einem Zeitplan (grau markiert) in die Liste
--- Ausgewählte Schaltung ---
(Nummer)              | Nummer des Zeitplans in der Liste (nicht editierbar)
Status                | Schaltet einen Zeitplan aktiv bzw. inaktiv
Regel                 | Vordefinierte Regeln, die bei der Schaltung berücksichtigt weden soll
Aktion                | Ausführende Aktion, also Ein- bzw. Ausschalten der Geräte
--- Wochentage ---
Montag - Sonntag      | Aktiv oder Inaktiv an diesem Wochentag
--- Zeitpunkt ---
Uhrzeit               | Zeitpunkt zum Schalten (__HINWEIS:__ Wird bei Auswahl eines Auslösers bzw. Ereignisses überschrieben!)
⭙ (Löschen)          | Uhrzeit löschen
--- Auslöser / Ereignis ---
Frühstens             | Frühster Zeitpunkt zum Schalten (in Bezug zum ausgewählten Ereignis)
⭙ (Löschen)          | Uhrzeit löschen (Frühstens)
Ereignis              | Ereignis auf das reagiert werden soll
Spätestens            | Spätester Zeitpunkt zum Schalten (in Bezug zum ausgewählten Ereignis)
⭙ (Löschen)          | Uhrzeit löschen (Spätestens)
--- Bedingung ---
HINZUFÜGEN            | Fügt eine Bedingung hinzu, welche zum Zeitpunkt des Schaltes erfüllt sein muss.

> 💡 Geräte ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Schaltvariablen       | Liste von Geräten (mehrere Geräte)
Skript                | Auszuführendes Skript (Status true/false wird als Array 'State' übergeben)

> ⚙️ Erweiterte Einstellungen ...

Name                  | Beschreibung
--------------------- | ---------------------------------
Externer Auslöser     | Variable vom Typ Boolean, welcher als Auslöser bei bedingtem Schalten benutzt werden soll.
Gleichzeitiges Ausführen eines Scriptes | Auswahl eines Skriptes, welches zusätzlich ausgeführt werden soll (IPS_ExecScript).

### 5. Statusvariablen

Die Statusvariablen werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

Name                 | Typ     | Beschreibung
-------------------- | ------- | ------------------------------
Schalter             | Boolean | Variable zum manuellen Auslösen der Geräteschaltung

_Hinweis:_ Die Variable _Schalter_ wird nur unter Berücksichtigung der erweiterten Einstellungen ('Zusätzlich noch eine normale Schaltervariable anlegen') angelegt.

### 6. Darstellungen

Es werden keine Darstellungen oder Profile benötigt.

### 7. Visualisierung

Man kann die Statusvariable (Schalter) direkt in der Visualisierung verlinken.

### 8. Befehlsreferenz

Ein direkter Aufruf von öffentlichen Funktionen ist nicht notwendig!

### 9. Versionshistorie

v2.0.20260201

* _NEU_: Projektumstrukturierung hin zu einer globalen CI/CD-Pipeline
* _NEU_: Kompatibilität auf IPS 8.1 hoch gesetzt
* _NEU_: Umstellung auf IPSModuleStrict
* _NEU_: Kompatibilität für IPS 8.2 vorbereitet
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
