# Tado Heating

![Image](../imgs/tado_logo.png)  

Mit Tado Heating kann eine Heizung smart gesteuert werden. 

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.  
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.  
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.  
Der Nutzer stimmt den oben angegebenen Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.  

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Automatikmodus schalten
* Soll-Temperatur anpassen
* Timer schalten
* Anzeige der Raumtemperatur
* Anzeige der Luftfeuchtigkeit

### 2. Voraussetzungen

- IP-Symcon ab Version 5.5

### 3. Software-Installation

* Bei kommerzieller Nutzung (z.B. als Einrichter oder Integrator) wenden Sie sich bitte zunächst an den Autor.
* Bei privater Nutzung wird das 'Tado'-Modul über den Module Store installiert.

### 4. Einrichten der Instanzen in IP-Symcon

Unter 'Instanz hinzufügen' kann das 'Tado Heating'-Modul mithilfe des Schnellfilters gefunden werden.  
Alternativ kann die 'Tado Configurator'-Instanz zur automatischen Einrichtung verwendet werden.  
Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)  

__Konfigurationsseite__:

Name            | Beschreibung
--------------- | ----------------------------------------------
Informationen   | Diverse Informationen über den Raum (Zone)

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name                | Typ       | Beschreibung
------------------- | --------- | ---------------------------
Mode                | boolean   | Manueller / Automatik Modus
SetpointTemperature | float     | Solltemperatur
HeatingTimer        | integer   | Timer
RoomTemperature     | float     | Raumtemperatur
AirHumidity         | float     | Luftfeuchtigkeit

#### Profile

Name                                | Typ
----------------------------------- | -------
TADO.InstanzID.Mode                 | boolean
TADO.InstanzID.SetpointTemperature  | float
TADO.InstanzID.HeatingTimer         | integer  

Wird die Instanz gelöscht, so werden automatisch die Profile gelöscht.  

### 6. WebFront

Der Automatikmodus kann ein- und ausgeschaltet werden.  
Die Solltemperatur kann angepasst werden.  
Der Timer kann gestellt werden.  

### 7. PHP-Befehlsreferenz

```text
void TADO_ToggleHeatingMode(integer $InstanceID, boolean $Mode);  
Schaltet den Automatikmodus (false = Manuell, true = Automatik).

Beispiel:
$data = TADO_ToggleHeatingMode(12345, false);
```  

```text
void TADO_SetHeatingTemperature(integer $InstanceID, float $Temperature);  
Verändert die Solltemperatur.

Beispiel:
$data = TADO_SetHeatingTemperature(12345, 22.5);
```  

```text
void TADO_SetHeatingTimer(integer $InstanceID, integer $Duration);  
Schaltet den Timer (0 = unendlich, 1 = bis zum nächsten Schaltpunkt, >300 Dauer in Sekunden).

Beispiel:
$data = TADO_SetHeatingTimer(12345, 3600);
```  

```text
void TADO_UpdateHeatingZoneState(integer $InstanceID);  
Aktualisiert den Staus des Raums (Zone). 

Beispiel:
$data = TADO_UpdateHeatingZoneState(12345);
```