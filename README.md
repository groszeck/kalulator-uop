# kalulator-uop  
**WordPress Plugin** ? Kalkulator wynagrodze? brutto ? netto do osadzania na stronach WordPress  

## Spis tre?ci  
1. [Opis projektu](#opis-projektu)  
2. [Funkcje](#funkcje)  
3. [Wymagania](#wymagania)  
4. [Instalacja](#instalacja)  
5. [U?ytkowanie](#u?ytkowanie)  
   - [Shortcode](#shortcode)  
   - [Widget](#widget)  
   - [Blok Gutenberg](#blok-gutenberg)  
6. [Komponenty](#komponenty)  
7. [Zale?no?ci](#zale?no?ci)  
8. [T?umaczenia i wieloj?zyczno??](#t?umaczenia-i-wieloj?zyczno??)  
9. [Bezpiecze?stwo](#bezpiecze?stwo)  
10. [Licencja](#licencja)  

---

## Opis projektu  
`kalulator-uop` (KPJ ? Pracownik Kalkulator) to wtyczka WordPress umo?liwiaj?ca interaktywne obliczenie wynagrodzenia netto dla trzech typ?w um?w (UoP, UZ, UoD). U?ytkownicy mog? por?wnywa? r??ne formy zatrudnienia, eksportowa? wyniki do PDF lub wys?a? je emailem, a tak?e udost?pnia? w mediach spo?eczno?ciowych.

## Funkcje  
- Trzy typy um?w:  
  - Umowa o prac? (UoP)  
  - Umowa zlecenie (UZ) ? opcjonalnie z ZUS lub bez ZUS  
  - Umowa o dzie?o (UoD)  
- Szczeg??owe wyliczenia ZUS, podatk?w i koszt?w uzyskania przychodu  
- Presety kwot: minimalna, ?rednia, popularne (3 000?15 000 z?)  
- Por?wnanie netto mi?dzy umowami  
- Generowanie PDF, wysy?ka email, udost?pnianie spo?eczno?ciowe  
- Shortcode, widget, blok Gutenberg  
- Panel administracyjny do zarz?dzania sk?adkami, progami i statystykami  
- REST API do asynchronicznych oblicze?  
- Nowoczesny, responsywny interfejs z wykresami (Chart.js)  
- Automatyczne aktualizacje stawek (opcjonalnie)  
- Zgodno?? z RODO, ochrona przed XSS  

## Wymagania  
- WordPress ? 5.5  
- PHP ? 7.4  
- MySQL ? 5.6 lub MariaDB ? 10.0  
- W??czone rozszerzenie cURL (je?li u?ywasz automatycznych aktualizacji)  

## Instalacja  

1. Pobierz paczk? wtyczki (`kalulator-uop.zip`).  
2. Wejd? w Kokpit ? Wtyczki ? Dodaj now? ? Wy?lij wtyczk? na serwer.  
3. Wybierz plik ZIP i kliknij **Zainstaluj teraz**.  
4. Po instalacji kliknij **Aktywuj wtyczk?**.  
5. (Opcjonalnie) Przejd? do Kokpit ? Ustawienia ? KPJ Kalkulator, aby dostosowa? stawki i progowe warto?ci.  

Alternatywnie WP-CLI:  
```bash
wp plugin install kalulator-uop.zip --activate
```

## U?ytkowanie  

### Shortcode  
Wklej w tre?? strony lub postu:  
```html
<!-- Podstawowy kalkulator: wszystkie typy um?w -->
[kpj_pracownik]

<!-- Tylko umowa o prac? -->
[kpj_pracownik typ="uop"]

<!-- Kalkulator z por?wnaniem -->
[kpj_pracownik porownanie="tak"]
```

### Widget  
1. Przejd? do Wygl?d ? Widgety.  
2. Dodaj widget **KPJ Kalkulator** do wybranej sekcji bocznej.  
3. Skonfiguruj tytu? i domy?lne ustawienia.

### Blok Gutenberg  
1. W edytorze Gutenberga kliknij **+**.  
2. Wybierz blok **KPJ Kalkulator**.  
3. Dostosuj opcje w panelu bocznym (typ umowy, por?wnanie, styl).

## Komponenty  
Poni?ej lista g??wnych plik?w i modu??w wraz z opisem:

- **pluginmain.php**  
  G??wny plik wtyczki z nag??wkiem, hookami aktywacji/dezaktywacji, autoloadem i enqueue.

- **kpj-pracownik.php**  
  Inicjalizator: rejestruje modu?y, ?aduje t?umaczenia, enqueue globalnych asset?w.

- **calculations.php**  
  Logika oblicze? UoP, UZ (z/bez ZUS), UoD, ZUS i podatk?w.

- **comparisonmodule.php**  
  Funkcje por?wnuj?ce netto dla r??nych um?w, formatowanie danych do wykresu.

- **exportmodule.php**  
  Generowanie PDF z wynikami, wysy?ka email, przygotowanie danych eksportowych.

- **shortcodes.php**  
  Rejestracja i renderowanie shortcode??w `[kpj_pracownik]` i wariant?w.

- **widget.php**  
  Definicja widgetu WP: formularz, formularz konfiguracji i zapis ustawie?.

- **rest-api.php**  
  Definicja tras REST API do AJAX-owych oblicze?, por?wna? i eksportu.

- **admin-settings.php**  
  Strona w panelu admina: zarz?dzanie stawkami ZUS/NFZ, progami podatkowymi, statystykami.

- **dashboardwidgetmodule.php**  
  Widget na pulpicie WP z liczb? kalkulacji, popularnymi kwotami i typami um?w.

- **gutenbergregistration.php**  
  Rejestracja bloku Gutenberg, enqueue skrypt?w dla edytora i frontendu.

- **frontend.js**  
  Obs?uga dynamicznych oblicze? w przegl?darce, AJAX, rysowanie wykres?w.

- **admin.js**  
  Skrypty do walidacji formularzy ustawie? i rysowania statystyk w panelu.

- **block.js**  
  Definicja interfejsu edytora dla bloku Gutenberg.

- **socialsharing.js**  
  Inicjalizacja przycisk?w do udost?pniania wynik?w w social media.

- **chart.js**  
  Biblioteka Chart.js do wykres?w ko?owych.

- **styles.css**  
  Responsywny CSS dla frontendu, admina i bloku Gutenberg.

- **composer.json**  
  Konfiguracja autoloadingu PSR-4 i ewentualnych zewn?trznych bibliotek.

- **kpj-pracownik.pot**  
  Szablon pliku do t?umacze?.

## Zale?no?ci  
- Chart.js (w zestawie lub z CDN)  
- WordPress REST API  
- Opcjonalnie: biblioteka PDF (np. TCPDF lub dompdf) zainstalowana przez Composer  

## T?umaczenia i wieloj?zyczno??  
- Domy?lny j?zyk: polski.  
- Plik `kpj-pracownik.pot` umo?liwia tworzenie t?umacze?.  
- W przysz?o?ci mo?na doda? pliki `.po`/`.mo` dla innych j?zyk?w.

## Bezpiecze?stwo  
- Walidacja i sanitizacja wszystkich danych wej?ciowych.  
- U?ycie Nonces w formularzach i AJAX.  
- Ochrona przed XSS i CSRF.  
- Brak przechowywania danych osobowych ? zgodno?? z RODO.

## Licencja  
Ten projekt jest dost?pny na licencji MIT. Szczeg??owe informacje w pliku `LICENSE`.  

---

Dzi?kujemy za skorzystanie z wtyczki `kalulator-uop`! W razie pyta? lub zg?osze? b??d?w, odwied? repozytorium GitHub lub skontaktuj si? z autorem.