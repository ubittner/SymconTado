# Tado Cooling

![Image](../imgs/tado_logo.png)  

Mit Tado Cooling kann eine Klimaanlage smart gesteuert werden. 

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

* Klimaanlage schalten
* Gerätemodus schalten
* Automatikmodus schalten
* Soll-Temperatur anpassen
* Lüftungsintensität schalten
* Lamellenbewegung schalten
* Timer schalten
* Anzeige der Raumtemperatur
* Anzeige der Luftfeuchtigkeit

### 2. Voraussetzungen

- IP-Symcon ab Version 5.5

### 3. Software-Installation

* Bei kommerzieller Nutzung (z.B. als Einrichter oder Integrator) wenden Sie sich bitte zunächst an den Autor.
* Bei privater Nutzung wird das 'Tado'-Modul über den Module Store installiert.

### 4. Einrichten der Instanzen in IP-Symcon

Unter 'Instanz hinzufügen' kann das 'Tado Cooling'-Modul mithilfe des Schnellfilters gefunden werden.  
Alternativ kann die 'Tado Configurator'-Instanz zur automatischen Einrichtung verwendet werden.  
Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)  

__Konfigurationsseite__:

Name            | Beschreibung
--------------- | ----------------------------------------------
Gerätetyp       | Auswahl des Gerätetyps 
Informationen   | Diverse Informationen über den Raum (Zone)

Sofern der Gerätetyp nicht auf Standard eingestellt ist,  
kann zusätzlich die Lüftungsintensität und Lamellenbewegung genutzt werden.

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name                | Typ       | Beschreibung
------------------- | --------- | ---------------------------
DeviceMode          | integer   | Gerätemodus 
Mode                | boolean   | Manueller / Automatik Modus
SetpointTemperature | float     | Solltemperatur
FanSpeed            | integer   | Lüftungsintensität
Swing               | integer   | Lamellenbewegung
CoolingTimer        | integer   | Timer
RoomTemperature     | float     | Raumtemperatur
AirHumidity         | float     | Luftfeuchtigkeit

#### Profile

Name                                | Typ
----------------------------------- | -------
TADO.InstanzID.DeviceMode           | integer
TADO.InstanzID.Mode                 | boolean
TADO.InstanzID.SetpointTemperature  | float
TADO.InstanzID.FanSpeed             | integer
TADO.InstanzID.Swing                | integer
TADO.InstanzID.CoolingTimer         | integer  

Wird die Instanz gelöscht, so werden automatisch die Profile gelöscht.  

### 6. WebFront

Die Klimaanlage kann ein- und ausgeschaltet werden.
Der Gerätemodus kann geschaltet werden.
Der Automatikmodus kann ein- und ausgeschaltet werden.  
Die Solltemperatur kann angepasst werden.  
Die Lüftungsintesität kann ausgewähöt werden.  
Die Lammellenbewegung kann ein- und ausgeschaltet werden.  
Der Timer kann gestellt werden.  

### 7. PHP-Befehlsreferenz

```text
void TADO_TogglePower(integer $InstanceID, boolean $State);  
Schaltet die Klimaanlage aus oder an.

$State:
false   = Aus
true    = An

Beispiel:
$data = TADO_TogglePower(12345, false);
```  

```text
void TADO_ToggleDeviceMode(integer $InstanceID, integer $Mode);  
Schaltet den Gerätemodus der Klimanalage.

$Mode:
0 = Cool    | Kühlen
1 = Dry     | Trocknen
2 = Fan     | Lüften
3 = Heat    | Heizen

Beispiel:
$data = TADO_ToggleDeviceMode(12345, 0);
```  

```text
void TADO_ToggleCoolingMode(integer $InstanceID, boolean $Mode);  
Schaltet den Automatikmodus.

$Mode:
false   = Manuell
true    = Automatik

Beispiel:
$data = TADO_ToggleCoolingMode(12345, false);
```  

```text
void TADO_SetCoolingTemperature(integer $InstanceID, float $Temperature);  
Verändert die Solltemperatur.

Beispiel:
$data = TADO_SetCoolingTemperature(12345, 15.5);
```  

```text
void TADO_SetFanSpeed(integer $InstanceID, int $Speed);  
Verändert die Lüftungsintensität.

$Speed:
0 = LOW (Gering)
1 = MIDDLE (Mittel)
2 = HIGH (Hoch)
3 = AUTO (Auto)

Beispiel:
$data = TADO_SetFanSpeed(12345, 3);
```  

```text
void TADO_SetSwingState(integer $InstanceID, int $State);  
Schaltet die Lamellenbewegung aus oder ein.

$State:
0 = OFF (Aus)
1 = ON (An)

Beispiel:
$data = TADO_SetSwingState(12345, 1);
```  

```text
void TADO_SetCoolingTimer(integer $InstanceID, integer $Duration);  
Schaltet den Timer.

$Duration:
0       = unendlich
1       = bis zum nächsten Schaltpunkt
>300    = Dauer in Sekunden

Beispiel:
$data = TADO_SetCoolingTimer(12345, 3600);
```  

```text
void TADO_UpdateCoolingZoneState(integer $InstanceID);  
Aktualisiert den Staus des Raums (Zone). 

Beispiel:
$data = TADO_UpdateCoolingZoneState(12345);
```