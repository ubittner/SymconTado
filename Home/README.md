# Tado Home

![Image](../imgs/tado_logo.png)  

Mit dem Tado Home kann der Geofencing Modus gesteuert werden. 

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

* Geofencing Modus (0 = Auto, 1 = Home, 2 = Away)

### 2. Voraussetzungen

- IP-Symcon ab Version 5.5

### 3. Software-Installation

* Bei kommerzieller Nutzung (z.B. als Einrichter oder Integrator) wenden Sie sich bitte zunächst an den Autor.
* Bei privater Nutzung wird das 'Tado'-Modul über den Module Store installiert.

### 4. Einrichten der Instanzen in IP-Symcon

Unter 'Instanz hinzufügen' kann das 'Tado Home'-Modul mithilfe des Schnellfilters gefunden werden.  
Alternativ kann die 'Tado Configurator'-Instanz zur automatischen Einrichtung verwendet werden.  
Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)  

__Konfigurationsseite__:

Name            | Beschreibung
--------------- | ----------------------------------------------
Informationen   | Diverse Informationen über das Zuhause (Home)

### 5. Statusvariablen und Profile

Die Statusvariablen/Kategorien werden automatisch angelegt. Das Löschen einzelner kann zu Fehlfunktionen führen.

#### Statusvariablen

Name            | Typ       | Beschreibung
--------------- | --------- | ----------------
GeofencingMode  | integer   | Geofencing Modus

#### Profile

Name                            | Typ
------------------------------- | -------
TADO.InstanzID.GeofencingMode   | integer
  
Wird die Instanz gelöscht, so werden automatisch die Profile gelöscht.  

### 6. WebFront

Ger Geofencing Modus kann geschaltet (Auto, Home, Away) werden.

### 7. PHP-Befehlsreferenz

```text
void TADO_SetGeofencingMode(integer $InstanceID, integer $Mode);  
Schaltet den Geofencing Modus (0 = Auto, 1 = Home, 2 = Away).

Beispiel:
$data = TADO_SetGeofencingMode(12345, 0);
```

```text
void TADO_UpdateHomeState(integer $InstanceID);  
Aktualisiert den Staus des Geofencing Modus. 

Beispiel:
$data = TADO_UpdateHomeState(12345);
```