# Tado Splitter

![Image](../imgs/tado_logo.png)  

Mit dem Tado Splitter wird die Kommunikation zu my.tado.com hergestellt. 

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

* Stellt die Verbindung zu my.tado.com her

### 2. Voraussetzungen

- IP-Symcon ab Version 5.5

### 3. Software-Installation

* Bei kommerzieller Nutzung (z.B. als Einrichter oder Integrator) wenden Sie sich bitte zunächst an den Autor.
* Bei privater Nutzung wird das 'Tado'-Modul über den Module Store installiert.

### 4. Einrichten der Instanzen in IP-Symcon

Unter 'Instanz hinzufügen' kann das 'Tado Splitter'-Modul mithilfe des Schnellfilters gefunden werden.  
Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)  

__Konfigurationsseite__:

Name            | Beschreibung
--------------- | --------------------------------
Aktiv           | De- bzw. aktiviert den Splitter  
E-Mail Adresse  | E-Mail Adresse für my.tado.com  
Kennwort        | Kennwort für my.tado.com  
Timeout         | Netzwerk-Timout  

### 5. Statusvariablen und Profile

Es werden keine Statusvariablen und Profile verwendet.  

### 6. WebFront

Die Splitter Instanz ist im WebFront nicht verfügbar. 

### 7. PHP-Befehlsreferenz

```text
Benutzerkonto:  

string TADO_GetAccount(integer $InstanceID);  
Liefert Informationen über das Tado Benutzerkonto.

Beispiel:
$data = TADO_GetAccount(12345);
```

```text
Zuhause (Home):  

string TADO_GetHome(integer $InstanceID, integer $HomeID);  
Liefert Informationen über das Zuhause (Home).

Beispiel:
$data = TADO_GetHome(12345, 1234);  
  

string TADO_GetHomeState(integer $InstanceID, integer $HomeID);  
Liefert Statusinformationen über das Zuhause (Home).

Beispiel:
$data = TADO_GetHomeState(12345, 1234);  
  

string TADO_SetPresenceLock(integer $InstanceID, integer $HomeID, integer $Mode);  
Schaltet den Geofencing Modus (0 = Auto, 1 = Home, 2 = Away).

Beispiel:
$data = TADO_SetPresenceLock(12345, 1234, 0);
```

```text
Raum (Zone):  

string TADO_GetZones(integer $InstanceID, integer $HomeID);  
Liefert Informationen über die Räume (Zonen) des Zuhauses (Home).

Beispiel:
$data = TADO_GetZones(12345);  
  

string TADO_GetZoneState(integer $InstanceID, integer $HomeID, integer $ZoneID);  
Liefert Statusinformationen über den Raum (Zone) des Zuhauses (Home).

Beispiel:
$data = TADO_GetZoneState(12345, 1234, 1);  
  

string TADO_StopManualMode(integer $InstanceID, integer $HomeID, integer $ZoneID);  
Stoppt dem manuellen Modus und schaltet zurück auf den intelligenten Zeitplan.

Beispiel:
$data = TADO_StopManualMode(12345, 1234, 1); 
```  

```text
Heizmodus (Smartes Thermostat, Smartes Heizkörper-Thermostat):  

string TADO_SetHeatingZoneTemperature(integer $InstanceID, integer $HomeID, integer $ZoneID, string $PowerState, float $Temperature)  
Setzt manuell die Temperatur eines Raums (Zone) des Zuhauses (Home) auf den die angegebene Temperatur.

Beispiel:
$data = TADO_SetHeatingZoneTemperature(12345, 1234, 1, 'ON', 23.5);
  

string SetHeatingZoneTemperatureTimer(integer $InstanceID, integer $HomeID, integer $ZoneID, string $PowerState, integer $Temperature, integer $DurationInSeconds) 
Setzt manuell die Temperatur eines Raums (Zone) des Zuhauses (Home) für eine bestimmte Zeit auf den die angegebene Temperatur.

Beispiel:
$data = SetHeatingZoneTemperatureTimer(12345, 1234, 1, 'ON', 23.5, 180);
  

string SetHeatingZoneTemperatureTimerNextTimeBlock(integer $InstanceID, integer $HomeID, integer $ZoneID, string $PowerState, integer $Temperature)
Setzt manuell die Temperatur eines Raums (Zone) des Zuhauses (Home)  bis zum nächsten Zeitblock auf den die angegebene Temperatur.

Beispiel:
$data = SetHeatingZoneTemperatureTimerNextTimeBlock(12345, 1234, 1, 'ON', 23.5);
 ```  

```text
Kühlmodus (Smartes Klimaanlagen-Thermostat):  

string TADO_SetCoolingZoneTemperature(integer $InstanceID, integer $HomeID, integer $ZoneID, string $PowerState, float $Temperature)  
Setzt manuell die Temperatur eines Raums (Zone) des Zuhauses (Home) auf den die angegebene Temperatur.

Beispiel:
$data = TADO_SetCoolingZoneTemperature(12345, 1234, 1, 'ON', 15.5);
  

string SetCoolingZoneTemperatureTimer(integer $InstanceID, integer $HomeID, integer $ZoneID, string $PowerState, integer $Temperature, integer $DurationInSeconds) 
Setzt manuell die Temperatur eines Raums (Zone) des Zuhauses (Home) für eine bestimmte Zeit auf den die angegebene Temperatur.

Beispiel:
$data = SetCoolingZoneTemperatureTimer(12345, 1234, 1, 'ON', 15.5, 180);
  

string SetCoolingZoneTemperatureTimerNextTimeBlock(integer $InstanceID, integer $HomeID, integer $ZoneID, string $PowerState, integer $Temperature)
Setzt manuell die Temperatur eines Raums (Zone) des Zuhauses (Home)  bis zum nächsten Zeitblock auf den die angegebene Temperatur.

Beispiel:
$data = SetCoolingZoneTemperatureTimerNextTimeBlock(12345, 1234, 1, 'ON', 15.5);
```

