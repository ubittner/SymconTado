![Image](../imgs/tado_logo.png)

# Splitter

Mit diesem Modul wird die Kommunikation zu my.tado.com hergestellt. 

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

- IP-Symcon ab Version 6.1

### 3. Software-Installation

* Bei kommerzieller Nutzung (z.B. als Einrichter oder Integrator) wenden Sie sich bitte zunächst an den Autor.
* Bei privater Nutzung wird das `tado° Modul` über den Module-Store installiert.

### 4. Einrichten der Instanzen in IP-Symcon

Unter `Instanz hinzufügen` kann die `tado° Splitter` Instanz mithilfe des Schnellfilters gefunden werden.
Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)  

##### Registrierung

❗️Ab dem 21.03.2025 kann die Anmeldung **NICHT** mehr mit Benutzernamen und Kennwort erfolgen.  
ℹ️ Es wird die neue [device code grant flow](https://support.tado.com/en/articles/8565472-how-do-i-authenticate-to-access-the-rest-api) verwendet.  
⚠️Bitte das tado° Modul aktualisieren und die neue Registrierung durchführen:  

* Schritt 1: Registrierung starten
* Schritt 2: Die Seite zur Anmeldung öffnet automatisch im Browser (Pop-ups müssen im Browser erlaubt sein)
![Image](../imgs/tado_login.png)
* Schritt 3: Initiale Token abrufen

__Konfigurationsseite__:

| Name           | Beschreibung                    |
|----------------|---------------------------------|
| Aktiv          | De- bzw. aktiviert den Splitter |
| Timeout        | Netzwerk-Timout                 |

### 5. Statusvariablen und Profile

Es werden keine Statusvariablen und Profile verwendet.  

### 6. WebFront

Der `tado° Splitter` ist im WebFront nicht verfügbar.

### 7. PHP-Befehlsreferenz

```text
Benutzerkonto:  
```

```text
string TADO_GetAccount(integer $InstanceID);  
Liefert Informationen über das Tado Benutzerkonto.

Beispiel:
$data = TADO_GetAccount(12345);
```

```text
Zuhause (Home):  
```

```text
string TADO_GetHome(integer $InstanceID, integer $HomeID);  
Liefert Informationen über das Zuhause (Home).

Beispiel:
$data = TADO_GetHome(12345, 1234);  
```

```text
string TADO_GetHomeState(integer $InstanceID, integer $HomeID);  
Liefert Statusinformationen über das Zuhause (Home).

Beispiel:
$data = TADO_GetHomeState(12345, 1234);  
```

```text
string TADO_SetPresenceLock(integer $InstanceID, integer $HomeID, integer $Mode);  
Schaltet den Geofencing Modus (0 = Auto, 1 = Home, 2 = Away).

Beispiel:
$data = TADO_SetPresenceLock(12345, 1234, 0);
```

```text
Raum (Zone):  
```

```text
string TADO_GetZones(integer $InstanceID, integer $HomeID);  
Liefert Informationen über die Räume (Zonen) des Zuhauses (Home).

Beispiel:
$data = TADO_GetZones(12345);  
```

```text
string TADO_GetZoneState(integer $InstanceID, integer $HomeID, integer $ZoneID);  
Liefert Statusinformationen über den Raum (Zone) des Zuhauses (Home).

Beispiel:
$data = TADO_GetZoneState(12345, 1234, 1);  
```

```text
string TADO_StopManualMode(integer $InstanceID, integer $HomeID, integer $ZoneID);  
Stoppt dem manuellen Modus und schaltet zurück auf den intelligenten Zeitplan.

Beispiel:
$data = TADO_StopManualMode(12345, 1234, 1); 
```  

```text
Heizmodus (Smartes Thermostat, Smartes Heizkörper-Thermostat):  
```

```text
string TADO_SetHeatingZoneTemperature(integer $InstanceID, integer $HomeID, integer $ZoneID, string $PowerState, float $Temperature)  
Setzt manuell die Temperatur eines Raums (Zone) des Zuhauses (Home) auf den die angegebene Temperatur.

Beispiel:
$data = TADO_SetHeatingZoneTemperature(12345, 1234, 1, 'ON', 23.5);
```

```text
string SetHeatingZoneTemperatureTimer(integer $InstanceID, integer $HomeID, integer $ZoneID, string $PowerState, integer $Temperature, integer $DurationInSeconds) 
Setzt manuell die Temperatur eines Raums (Zone) des Zuhauses (Home) für eine bestimmte Zeit auf den die angegebene Temperatur.

Beispiel:
$data = SetHeatingZoneTemperatureTimer(12345, 1234, 1, 'ON', 23.5, 180);
```

```text
string SetHeatingZoneTemperatureTimerNextTimeBlock(integer $InstanceID, integer $HomeID, integer $ZoneID, string $PowerState, integer $Temperature)
Setzt manuell die Temperatur eines Raums (Zone) des Zuhauses (Home)  bis zum nächsten Zeitblock auf den die angegebene Temperatur.

Beispiel:
$data = SetHeatingZoneTemperatureTimerNextTimeBlock(12345, 1234, 1, 'ON', 23.5);
```

```text
Kühlmodus (Smartes Klimaanlagen-Thermostat):  
```  

```text
string TADO_SetCoolingZone(interger $InstanceID, integer $HomeID, integer $ZoneID, string $Overlay);
Setzt einen Raum (Zone) des Zuhauses (Home) auf die angegebene Werte.
$Overlay muss für die entsprehcneden Parameter angegeben werden:

['setting']['power']        OFF | ON
['setting']['mode']         COOL | HEAT | DRY | FAN
['setting']['type']         AIR_CONDITIONING
['setting']['fanSpeed']     LOW | MIDDLE | HIGH | AUTO
['setting']['temperature']  CELSIUS | FAHRENHEIT
['setting']['swing']        OFF | ON
     
Beispiel:
$overlay = '{"termination":{"typeSkillBasedApp":"MANUAL"},"setting":{"mode":"DRY","type":"AIR_CONDITIONING","power":"ON"}}';
$data = TADO_SetCoolingZone(12345, 1234, 1, $overlay);
```

```text
Funktion veraltet ! Wird bei der nächsten Aktualisierung gelöscht!

string TADO_SetCoolingZoneTemperature(integer $InstanceID, integer $HomeID, integer $ZoneID, string $PowerState, float $Temperature)  
Setzt manuell die Temperatur eines Raums (Zone) des Zuhauses (Home) auf den die angegebene Temperatur.

Beispiel:
$data = TADO_SetCoolingZoneTemperature(12345, 1234, 1, 'ON', 15.5);
```

```text
Funktion veraltet ! Wird bei der nächsten Aktualisierung gelöscht!

string TADO_SetCoolingZoneTemperatureEx(integer $InstanceID, integer $HomeID, integer $ZoneID, string $PowerState, string $DeviceMode, float $Temperature, string $FanSpeed, string $Swing)  
Setzt manuell die Temperatur eines Raums (Zone) des Zuhauses (Home) auf den die angegebene Temperatur.
Zusätzlich kann der Gerätemodus, die Lüftungsintensität und die Lamellenbewegung angegeben werden.

Beispiel:
$data = TADO_SetCoolingZoneTemperatureEx(12345, 1234, 1, 'ON', 'COOL', 15.5, 'MID', 'ON');
```

```text
Funktion veraltet ! Wird bei der nächsten Aktualisierung gelöscht!

string SetCoolingZoneTemperatureTimer(integer $InstanceID, integer $HomeID, integer $ZoneID, string $PowerState, integer $Temperature, integer $DurationInSeconds) 
Setzt manuell die Temperatur eines Raums (Zone) des Zuhauses (Home) für eine bestimmte Zeit auf den die angegebene Temperatur.

Beispiel:
$data = SetCoolingZoneTemperatureTimer(12345, 1234, 1, 'ON', 15.5, 180);
```

```text
Funktion veraltet ! Wird bei der nächsten Aktualisierung gelöscht!

string SetCoolingZoneTemperatureTimerEx(integer $InstanceID, integer $HomeID, integer $ZoneID, string $PowerState, string $DeviceMode, integer $Temperature, integer $DurationInSeconds, string $FanSpeed, string $Swing) 
Setzt manuell die Temperatur eines Raums (Zone) des Zuhauses (Home) für eine bestimmte Zeit auf den die angegebene Temperatur.
Zusätzlich kann der Gerätemodus, die Lüftungsintensität und die Lamellenbewegung angegeben werden.

Beispiel:
$data = SetCoolingZoneTemperatureTimerEx(12345, 1234, 1, 'ON', 'COOL', 15.5, 180, 'MID', 'ON');
```

```text
Funktion veraltet ! Wird bei der nächsten Aktualisierung gelöscht!

string SetCoolingZoneTemperatureTimerNextTimeBlock(integer $InstanceID, integer $HomeID, integer $ZoneID, string $PowerState, integer $Temperature)
Setzt manuell die Temperatur eines Raums (Zone) des Zuhauses (Home)  bis zum nächsten Zeitblock auf den die angegebene Temperatur.

Beispiel:
$data = SetCoolingZoneTemperatureTimerNextTimeBlock(12345, 1234, 1, 'ON', 15.5);
```

```text
Funktion veraltet ! Wird bei der nächsten Aktualisierung gelöscht!

string SetCoolingZoneTemperatureTimerNextTimeBlockEx(integer $InstanceID, integer $HomeID, integer $ZoneID, string $PowerState, string $DeviceMode, integer $Temperature, string $FanSpeed, string $Swing)
Setzt manuell die Temperatur eines Raums (Zone) des Zuhauses (Home)  bis zum nächsten Zeitblock auf den die angegebene Temperatur.
Zusätzlich kann der Gerätemodus, die Lüftungsintensität und die Lamellenbewegung angegeben werden.

Beispiel:
$data = SetCoolingZoneTemperatureTimerNextTimeBlockEx(12345, 1234, 1, 'ON', 'COOL', 15.5, 'MID', 'ON');
```

```text
Mobile Geräte:  
```  

```text
string TADO_GetMobileDevices(integer $InstanceID, integer $HomeID)  
Liefert Informationen über die mobilen Geräte, die das ausgewählte Haus steuern.

Beispiel:
$data = TADO_GetMobileDevices(12345, 987654);
```

```text
string TADO_GetMobileDeviceInfo(integer $InstanceID, integer $HomeID, integer $DeviceID)  
Liefert Informationen über ein mobiles Geräte.

Beispiel:
$data = TADO_GetMobileDeviceInfo(12345, 987654, 123456);
```