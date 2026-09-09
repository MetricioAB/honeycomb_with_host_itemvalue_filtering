# Honeycomb with HostItem Filter

## Installation

### Förutsättningar

- Tillgång till filsystemet där Zabbix frontend körs och ett Zabbix-konto med Super admin-behörighet för att aktivera modulen.
- Webbserverns/PHP-processens användare måste kunna läsa modulens filer och komma åt dess kataloger.
- Kontrollera kompatibiliteten i en testmiljö med samma Zabbix- och PHP-version som din installation. Projektet har ännu ingen verifierad lista över stödda Zabbix-versioner.

### Kopiera modulen

Placera modulen i en egen underkatalog till **frontendens** `modules`-katalog. Frontendens rot är katalogen som innehåller `index.php` och `zabbix.php`; sökvägen varierar mellan installationer. Modulen ska installeras på webbservern eller i frontendcontainern, även om Zabbix server körs på en annan maskin.

Exempel på kopiering på en Linux-server, från projektets rotkatalog. Ersätt `/sokvag/till/zabbix/frontend` med din faktiska frontendrot innan du kör kommandona:

```sh
ZABBIX_FRONTEND=/sokvag/till/zabbix/frontend
sudo mkdir -p "$ZABBIX_FRONTEND/modules/honeycomb_with_host_item_filter"
sudo cp -R Widget.php manifest.json actions assets includes views \
  "$ZABBIX_FRONTEND/modules/honeycomb_with_host_item_filter/"
```

Kontrollera att strukturen blir följande, utan en extra nästlad projektkatalog:

```text
<frontend>/modules/honeycomb_with_host_item_filter/
├── manifest.json
├── Widget.php
├── actions/
├── assets/
├── includes/
└── views/
```

`README.md`, `AGENTS.md` och `tests/` behövs inte för drift. Vid containerdrift ska filerna ingå i en beständig volym eller en egen containerimage så att de finns kvar när containern återskapas.

### Aktivera och lägg till widgeten

1. Logga in i Zabbix som Super admin.
2. Öppna **Administration → General → Modules**. Menynamn och placering kan variera med version och språk.
3. Klicka på **Scan directory**.
4. Leta upp **Honeycomb with HostItem Filter** och aktivera modulen med **Enable**.
5. Öppna en dashboard, välj **Edit dashboard** och **Add widget**.
6. Välj typen **Honeycomb with HostItem Filter**, konfigurera hostar och items och spara widgeten samt dashboarden.

Installationsflödet beskrivs också i [Zabbix dokumentation om frontendmoduler](https://www.zabbix.com/documentation/8.0/en/manual/extensions/frontendmodules). Välj dokumentationsversionen som motsvarar din installation.

### Uppdatera en befintlig installation

1. Säkerhetskopiera den installerade modulkatalogen till en plats utanför frontendens `modules`-katalog.
2. Kopiera de nya filerna till samma modulkatalog enligt ovan. Behåll modul-ID:t `honeycomb_with_filter` och katalognamnet `honeycomb_with_host_item_filter`.
3. Kör **Scan directory** igen om manifestet har ändrats och kontrollera att modulen är aktiverad.
4. Ladda om dashboardsidan, vid behov med hård omladdning för nya JavaScript-filer. Öppna widgetens inställningar för att konfigurera nya filterfält.
5. Utför kontrollen längst ned i denna README. Befintliga widgetar utan valt hostfilter ska fortsätta visa samma data.

### Felsökning

- **Modulen syns inte efter skanning:** kontrollera att `manifest.json` ligger direkt i modulkatalogen och att webbservern kan läsa filerna.
- **Wrong Widget.php class name:** kontrollera att alla filer från samma version har kopierats. Manifestets namespace är `Honeycomb_with_filter` och `Widget.php` ska deklarera `Modules\Honeycomb_with_filter`. Katalognamnet är inte PHP-namespace.
- **Widgettypen saknas:** kontrollera att modulen är aktiverad och tillgänglig för användarens roll.
- **Gamla PHP-ändringar ligger kvar:** om PHP OPcache inte kontrollerar filändringar behöver cachen återställas enligt serverns driftrutin.
- **PHP-fel eller tom widget:** kontrollera webbserverns/PHP:s fellogg och kompatibiliteten med installerad Zabbix-version.

## Filtrera bort hostar via itemvärde

1. Välj hostar och de **Item patterns** som ska visas som celler.
2. Välj ett itemnamn i **Host filter item patterns**. Namnet/mönstret används på varje host, så motsvarande items behöver ha samma namn eller matcha samma mönster.
3. Ange exempelvis `0` i **Hide host when value equals**.

Om något matchande filteritem på en host har det angivna senaste värdet döljs **alla** celler från den hosten. Filteritemet behöver inte ingå i de items eller itemtaggar som visas. Filtreringen utvärderas vid varje datauppdatering och sker före begränsningen av antalet celler.

- Tomt **Host filter item patterns** stänger av filtret, även för befintliga widgetar.
- Flera namn eller jokertecken kan matcha flera filteritems. En enda värdeträff räcker för att dölja hosten.
- Jämförelsen använder råvärdet, före value mapping och enhetsformatering. Numeriska items jämförs numeriskt (`0` matchar `0.0`); text/logg jämförs exakt och skiftlägeskänsligt.
- Endast aktiva filteritems med numeriska, text- eller loggvärden används.
- Saknas filteritem eller historik inom Zabbix inställda historikperiod för senaste värden behålls hosten.

## Kontroll

Kör `php tests/host_filter.php` för isolerade regressionstester av hostfiltret. Testet använder simulerade API- och historiksvar; det ersätter inte ett integrationstest i Zabbix.

Kontrollera även i Zabbix med två hostar som har samma filteritem: värde `0` på den ena och `1` på den andra. Endast den andra hostens celler ska visas. Ändra värdet och invänta widgetens uppdatering för att kontrollera att hosten återkommer.
