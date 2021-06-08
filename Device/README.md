# Tado Device

![Image](../imgs/tado_logo.png)  

Mit dem Tado Device kann der Batteriestatus eines Gerätes (Smartes Thermostat, Smartes Heizkörper-Thermostat) angezeigt werden. 

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

* Batteriestatus

### 2. Voraussetzungen

- IP-Symcon ab Version 5.5

### 3. Software-Installation

* Bei kommerzieller Nutzung (z.B. als Einrichter oder Integrator) wenden Sie sich bitte zunächst an den Autor.
* Bei privater Nutzung wird das 'Tado'-Modul über den Module Store installiert.

### 4. Einrichten der Instanzen in IP-Symcon

Unter 'Instanz hinzufügen' kann das 'Tado Device'-Modul mithilfe des Schnellfilters gefunden werden.  
Alternativ kann die 'Tado Configurator'-Instanz zur automatischen Einrichtung verwendet werden.  
Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)  

__Konfigurationsseite__:

Name            | Beschreibung
--------------- | ----------------------------------------------
Informationen   | Diverse Informationen über das Gerät (Device)

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name            | Typ       | Beschreibung
--------------- | --------- | ----------------
BatteryState    | integer   | Batteriestatus

#### Profile

Es werden keine zusätzlichen Profile verwendet.  

### 6. WebFront

Der Batteriezustand des Gerätes wird im WebFront angezeigt.

### 7. PHP-Befehlsreferenz

```text
void TADO_UpdateDeviceState(integer $InstanceID);  
Aktualisiert den Staus des Gerätes. 

Beispiel:
$data = TADO_UpdateDeviceState(12345);
```