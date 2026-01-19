# EEG Verwaltung – WordPress Plugin  
# EEG Management – WordPress Plugin

> ⚠️ **Projektstatus / Project Status**  
> Dieses Plugin befindet sich in aktiver Entwicklung und ist **noch nicht produktiv einsetzbar**.  
> This plugin is under active development and **not ready for production use**.

---

## 🇩🇪 Deutsch

### Beschreibung

**EEG Verwaltung** ist ein WordPress-Plugin zur Unterstützung von  
**Erneuerbaren-Energie-Gemeinschaften (EEG)** bei:

- dem Onboarding neuer Mitglieder
- der Verwaltung von Mitgliedern
- der Verwaltung von Zählpunkten
- dem Export von CSV-Dateien für **EEG Faktura (eegfaktura.at)**

Das Plugin richtet sich an technisch und organisatorisch verantwortliche Betreiber von EEGs und soll bestehende manuelle Prozesse vereinfachen.

---

### Funktionsumfang (aktueller Stand)

#### Onboarding
- Frontend-Anmeldeseite für neue Mitglieder
- Erfassung grundlegender Mitgliedsdaten
- Speicherung in der WordPress-Datenbank

#### Mitgliederverwaltung
- Backend-Verwaltung von Mitgliedern
- Bearbeiten und Löschen von Einträgen
- Statusbasierte Verwaltung (z. B. aktiv / inaktiv)

#### Zählpunktverwaltung
- Verwaltung von Zählpunkten
- Zuordnung von Zählpunkten zu Mitgliedern
- Unterstützung mehrerer Zählpunkte pro Mitglied

#### CSV-Export
- Generierung von CSV-Dateien
- Struktur orientiert sich an Importen für **EEG Faktura**
- Manueller Export über das WordPress-Backend

---

### Technische Grundlagen

- WordPress-Plugin (kein externes Framework)
- PHP >= 8.0
- Nutzung der WordPress-Datenbank
- Erweiterbar (z. B. REST-API, Validierungen, Rollenmodelle)

---

### Installation (Entwicklung)

```bash
git clone https://github.com/michael-neumann/eeg-verwaltung.git
