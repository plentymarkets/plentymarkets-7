# Release Notes für PAYONE

## 2.6.0 (2023-05-24)

### Behoben
- Hinweistexte auf Rechnungsdokumenten werden nun in der entsprechenden Sprache aufgedruckt.
- 3D-sicherer Workflow

## 2.5.3 (2022-10-27)

### Behoben
- Zahlungen können nun in allen Fällen wieder abgeschlossen werden.

## 2.5.2

### Behoben
- Wenn es bei einer Rückerstattung zu einem Fehler kam wurde dieser nicht korrekt dargestellt.
- Fehler bei der Rückerstattung behoben.

## 2.5.1

### Behoben
- Die SEO Einstellungen bzgl. dem Trailing Slash werden beim Aufbau der Routen nun korrekt berücksichtigt, damit es zu keinen fehlerhaften Weiterleitungen kommt.

## 2.5.0

### Hinzugefügt
- Die Zahlungsarten des Plugins können nun auch im MyAccount dazu genutzt werden einen Auftrag nachträglich zu bezahlen.

## 2.4.0

### Hinzugefügt
 * Folgenden Zahlungsarten wurden hinzugefügt:
   - Klarna "Slice It" (Installments)
   - Klarna "Pay Later" (Invoice)
   - Klarna "Pay Now" (Direct Debit)
   - Klarna "Pay Now" (Direct Bank Transfer)
 * Im Assistenten kann nun für jede Zahlungsart ein individuelles Icon für den Webshop hinterlegt werden.

## 2.3.1

### Behoben
- Beim gesichteren Rechnungskauf in Verbindung mit Merkmalen/Bestellmerkmalen/Bestelleigenschaften konnte es zu einem Fehler kommen. Dieser wurde behoben. 

## 2.3.0

### Hinzugefügt
- Die Auswahl der verfügbaren Lieferländer wurde erweitert.

### Behoben
- Beim gesicherten Rechnungskauf werden die Artikeldaten nun korrekt übergeben.

## 2.2.0

### Hinzugefügt
- Beim Kopieren eines Plugin Sets werden nun auch die Einstellungen des Assistenten mit kopiert.

## 2.1.3

### Behoben
- Bei der Prüfung, ob eine Zahlungsart für die nachträgliche Bezahlung zur Verfügung steht, werden die entsprechenden Regeln angewendet, die auch für den Checkout gelten.
- Trailing Slash Einstellung aus dem Backend wird nun korrekt verarbeitet.

## 2.1.2

### Behoben
- Die erlaubten Arten von Kreditkarten wurden nicht korrekt dargestellt. Dies wurde nun behoben.

## 2.1.1

### Behoben
- Durch eine fehlerhafte Konfiguration des Plugins konnte es Fehler im Webshop geben, dies wurde behoben.  

## 2.1.0

### Hinzugefügt
- Bestelleigenschaften können nun korrekt verarbeitet werden.

## 2.0.0

### TODO
- Der Assistent muss in jedem verknüpften Plugin-Set durchlaufen werden, um das Plugin zu konfigurieren.

### Hinzugefügt
- Die Einstellungen für das Payone-Plugin wurden in einen Assistenten überführt. Es ist nun möglich das Plugin pro Plugin-Set und pro Mandant zu konfigurieren.

## 1.3.0

### Hinzugefügt
- Die erlaubten Lieferländer können in der Konfiguration des Plugins nun per Checkbox ausgewählt werden.

### Geändert
- Für Bestellungen mit gesichertem Rechnungskauf können keine Rechnungen erstellt werden.

## 1.2.2

### Behoben
- Ein Fehler bei der Kaufabwicklung mit der Zahlungsart "Kreditkarte" wurde behoben.

## 1.2.1

### Behoben
- Die Zahlung wird nun in der korrekten Fremdwährung gespeichert, wenn diese von der Systemwährung abweicht.

## 1.2.0

### Hinzugefügt
- Zahlungsart "Amazon Pay" hinzugefügt

## 1.1.14

### Hinzugefügt
- Zahlungsart "Secure Invoice" hinzugefügt

## 1.1.13

### Geändert
- Icon für das Backend hinzugefügt
- User Guide aktualisiert

## 1.1.12

### Geändert
- Logging des Datenaustausches zur Payone Schnittstelle optimiert.

## 1.1.11

### Geändert
- Logos und Bilder getauscht
- Funktionalitäten hinzugefügt für Backend-Sichtbarkeiten und Backend-Name

## 1.1.10

### Behoben
- Auftragsnotizen bei Rückerstattungen werden nun wieder korrekt geschrieben.

## 1.1.9

### Behoben
- Teilrückerstattungen können nun auch mehrfach ausgeführt werden, dies führte unter bestimmten Konstelationen zu einem Fehler.

## 1.1.8

### Behoben
- Teilrückerstattungen werden nun über den korrekten Betrag ausgeführt.

### Geändert
- Rückerstattungen werden nun dem Auftrag zugeordnet von dem diese ausgeführt wurden.
- Ladezeiten des Plugins wurden verbessert.

## 1.1.7

### Hinzugefügt
- Rückzahlungen werden nun per Notiz am Auftrag hinterlegt. Dazu muss in der Konfiguration eine UserID hinterlegt werden.

## 1.1.6

### Behoben
- Fehlerhafte Auftragsanlage behoben

## 1.1.5

### Behoben
- Aufträge die von externe Importiert werden und eine Zahlungsart von Payone nutzen werden nun korrekt angelegt und nicht durch das Plugin abgefangen.

## 1.1.4

### Geändert
- Das Payone SDK wurde umgezogen.

### Hinzugefügt
- Die Zahlungsart Amazon Pay wurde für das Backend hinzugefügt.

### Behoben
- Einige Meldungen im Log

## 1.1.3

### Geändert
- Optimierungen für die Eingabe des Geburtsdatums im Bestellprozess.
- User Guide aktualisiert.

## 1.1.2

### Geändert
- Die Eingabe des Geburtsdatums ist nun im Bestellprozess ein Pflichtfeld. Dieses Feld muss in den Einstellungen von Ceres für den Bestellprozess aktiviert werden.

### Hinzugefügt
- Netto Bestellungen können nun durchgeführt werden.

## 1.1.1

### Behoben
- Ein Fehler, der während der Zahlungsabwicklung zur Anlage von Auftragsduplikaten geführt hatte, wurde behoben
- Ein Fehler bei der Kreditkartentypauswahl wurde behoben

## 1.1.0

### Geändert
- Supportübernahme durch plentysystems
- Icons getauscht und Beschreibungen aktualisiert
- Updates an den User Guides in deutscher und englischer Sprache

## 1.0.9

### Geändert
- Hinweistext im User Guide ergänzt

## 1.0.8

### Geändert
- Informationen im Support-Tab aktualisiert
- Changelog aktualisiert

## 1.0.7

### Geändert
- Aktualisierung der config.json Datei zur Bereitstellung des neuen Plugin-Formats

### Hinzugefügt
- Übersetzungen hinzugefügt

### Geändert
- User Guide aktualisiert
- guzzle/httpguzzle Version hinzugefügt, um Kompatibilität mit PayPal zu erreichen

## 1.0.6

### Hinzugefügt
- Die Zahlungsart Sofort wird jetzt im Frontend angezeigt
- Die Zahlungsart Paydirekt wird jetzt im Frontend angezeigt
- Die Zahlungsart Gesicherte Rechnung wird jetzt im Frontend angezeigt
- Die Zahlungsart PayPal wird jetzt im Frontend angezeigt

### Geändert
- Verbesserte Darstellung der Popup-Meldung bei fehlerhaften Zahlungen

## 1.0.5

### Geändert
- Logos und Name des Plugins aktualisiert

## 1.0.4

### Geändert
- Dokumentation aktualisiert

## 1.0.3

### Hinzugefügt
- Dokumentation in englischer Sprache hinzugefügt

### Geändert
- Die aktuelle Payone PHP API wird nun genutzt

## 1.0.2

### Geändert
- Scriptloader wird nun genutzt, um Payone Skripte in Templates einzubinden

## 1.0.1

### Geändert
- Plugin-Dokumentation aktualisiert

## 1.0.1
Veröffentlichung des Plugins inklusive Unterstützung der folgenden Zahlungsarten:

- Rechnung
- Vorkasse
- Nachnahme
- Lastschrift
- Kreditkarte
- Kreditkarte 3DS
