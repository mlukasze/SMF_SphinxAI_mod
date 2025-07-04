<?php
/**
 * Sphinx AI Search Language File (Polish)
 */

$txt['sphinx_ai_search'] = 'Wyszukiwanie AI';
$txt['sphinx_ai_search_admin'] = 'Administracja wyszukiwania AI';
$txt['sphinx_ai_search_button'] = 'Szukaj';
$txt['sphinx_ai_search_placeholder'] = 'Zapytaj mnie o cokolwiek na forum...';
$txt['sphinx_ai_search_results'] = 'Wyniki wyszukiwania';
$txt['sphinx_ai_summary'] = 'Podsumowanie AI';
$txt['sphinx_ai_sources'] = 'Źródła';
$txt['sphinx_ai_confidence'] = 'Pewność';

// Admin strings
$txt['sphinx_ai_search_settings'] = 'Ustawienia';
$txt['sphinx_ai_search_index'] = 'Indeksowanie';
$txt['sphinx_ai_search_stats'] = 'Statystyki';

// Settings
$txt['sphinx_ai_model_path'] = 'Ścieżka modelu';
$txt['sphinx_ai_model_path_desc'] = 'Ścieżka do pliku modelu OpenVINO (np. /ścieżka/do/model.xml)';
$txt['sphinx_ai_max_results'] = 'Maksymalna liczba wyników';
$txt['sphinx_ai_max_results_desc'] = 'Maksymalna liczba wyników wyszukiwania do zwrócenia (1-100)';
$txt['sphinx_ai_summary_length'] = 'Długość podsumowania';
$txt['sphinx_ai_summary_length_desc'] = 'Maksymalna długość podsumowań generowanych przez AI w znakach (50-500)';
$txt['sphinx_ai_auto_index'] = 'Automatyczne indeksowanie';
$txt['sphinx_ai_auto_index_desc'] = 'Automatycznie indeksuj nowe posty w miarę ich tworzenia';
$txt['sphinx_ai_settings_saved'] = 'Ustawienia zostały pomyślnie zapisane!';

// Indexing
$txt['sphinx_ai_total_indexed'] = 'Całkowita liczba zaindeksowanych postów';
$txt['sphinx_ai_last_indexed'] = 'Ostatnio zaindeksowane';
$txt['sphinx_ai_start_indexing'] = 'Rozpocznij indeksowanie';
$txt['sphinx_ai_indexing_started'] = 'Proces indeksowania został uruchomiony w tle';
$txt['sphinx_ai_confirm_reindex'] = 'To spowoduje ponowne zaindeksowanie całej zawartości forum. Kontynuować?';

// Statistics
$txt['sphinx_ai_total_searches'] = 'Całkowita liczba wyszukiwań';
$txt['sphinx_ai_last_30_days'] = 'Ostatnie 30 dni';
$txt['sphinx_ai_avg_results'] = 'Średnia liczba wyników na wyszukiwanie';
$txt['sphinx_ai_popular_queries'] = 'Popularne zapytania wyszukiwania';
$txt['sphinx_ai_query'] = 'Zapytanie';
$txt['sphinx_ai_search_count'] = 'Liczba wyszukiwań';
$txt['sphinx_ai_no_data'] = 'Brak dostępnych danych';

// General
$txt['replies'] = 'Odpowiedzi';
$txt['views'] = 'Wyświetlenia';
$txt['save'] = 'Zapisz';

// Permissions
$txt['permissionname_sphinx_ai_search'] = 'Korzystanie z wyszukiwania AI';
$txt['permissionhelp_sphinx_ai_search'] = 'Pozwól członkom korzystać z funkcji wyszukiwania AI';
$txt['cannot_sphinx_ai_search'] = 'Nie masz uprawnień do korzystania z funkcji wyszukiwania AI';

// Errors
$txt['sphinx_ai_error_no_query'] = 'Proszę wprowadzić zapytanie wyszukiwania';
$txt['sphinx_ai_error_short_query'] = 'Zapytanie wyszukiwania musi mieć co najmniej 2 znaki';
$txt['sphinx_ai_error_query_too_long'] = 'Zapytanie wyszukiwania jest za długie. Ogranicz do 1000 znaków.';
$txt['sphinx_ai_error_model_not_found'] = 'Nie znaleziono modelu AI. Sprawdź ścieżkę modelu w ustawieniach.';
$txt['sphinx_ai_error_python_not_found'] = 'Nie znaleziono interpretera Python. Upewnij się, że Python jest zainstalowany.';
$txt['sphinx_ai_error_dependencies'] = 'Wymagane zależności Python nie są zainstalowane. Sprawdź przewodnik instalacji.';
$txt['sphinx_ai_error_index_empty'] = 'Indeks wyszukiwania jest pusty. Najpierw uruchom indeksowanie.';
$txt['sphinx_ai_error_timeout'] = 'Żądanie wyszukiwania przekroczyło limit czasu. Spróbuj ponownie.';

// Success messages
$txt['sphinx_ai_success_indexed'] = 'Pomyślnie zaindeksowano {count} postów';
$txt['sphinx_ai_success_settings'] = 'Ustawienia zostały pomyślnie zapisane';

// Info messages
$txt['sphinx_ai_info_indexing'] = 'Indeksowanie w toku... Może to potrwać kilka minut.';
$txt['sphinx_ai_info_first_time'] = 'Konfiguracja po raz pierwszy: Skonfiguruj ścieżkę modelu AI i uruchom początkowe indeksowanie.';
$txt['sphinx_ai_info_requirements'] = 'Ta funkcja wymaga Python 3.8+, OpenVINO i Hugging Face Transformers.';

// Advanced features
$txt['sphinx_ai_preferences'] = 'Preferencje wyszukiwania AI';
$txt['sphinx_ai_statistics'] = 'Statystyki wyszukiwania AI';
$txt['sphinx_ai_dark'] = 'Ciemny motyw AI';

// Search interface
$txt['sphinx_ai_search_tips'] = 'Wskazówki wyszukiwania';
$txt['sphinx_ai_search_help'] = 'Pomoc wyszukiwania';
$txt['sphinx_ai_no_results'] = 'Nie znaleziono wyników dla tego zapytania.';
$txt['sphinx_ai_try_different'] = 'Spróbuj użyć innych słów kluczowych lub zapytania.';
$txt['sphinx_ai_loading'] = 'Wyszukiwanie...';
$txt['sphinx_ai_search_again'] = 'Wyszukaj ponownie';

// Performance and monitoring
$txt['sphinx_ai_performance'] = 'Wydajność';
$txt['sphinx_ai_response_time'] = 'Czas odpowiedzi';
$txt['sphinx_ai_cache_hit_rate'] = 'Współczynnik trafień cache';
$txt['sphinx_ai_memory_usage'] = 'Użycie pamięci';
$txt['sphinx_ai_cpu_usage'] = 'Użycie procesora';

// Configuration
$txt['sphinx_ai_config'] = 'Konfiguracja';
$txt['sphinx_ai_database_config'] = 'Konfiguracja bazy danych';
$txt['sphinx_ai_sphinx_config'] = 'Konfiguracja Sphinx';
$txt['sphinx_ai_ai_config'] = 'Konfiguracja AI';
$txt['sphinx_ai_cache_config'] = 'Konfiguracja cache';

// Maintenance
$txt['sphinx_ai_maintenance'] = 'Konserwacja';
$txt['sphinx_ai_optimize_index'] = 'Optymalizuj indeks';
$txt['sphinx_ai_clear_cache'] = 'Wyczyść cache';
$txt['sphinx_ai_rebuild_index'] = 'Przebuduj indeks';
$txt['sphinx_ai_check_status'] = 'Sprawdź status';

// API and integration
$txt['sphinx_ai_api'] = 'API';
$txt['sphinx_ai_api_key'] = 'Klucz API';
$txt['sphinx_ai_api_endpoints'] = 'Punkty końcowe API';
$txt['sphinx_ai_integration'] = 'Integracja';
$txt['sphinx_ai_webhooks'] = 'Webhooks';

// Multi-language support
$txt['sphinx_ai_language'] = 'Język';
$txt['sphinx_ai_auto_detect'] = 'Automatyczne wykrywanie';
$txt['sphinx_ai_supported_languages'] = 'Obsługiwane języki';

// Quality and relevance
$txt['sphinx_ai_relevance'] = 'Trafność';
$txt['sphinx_ai_quality_score'] = 'Wynik jakości';
$txt['sphinx_ai_similarity'] = 'Podobieństwo';
$txt['sphinx_ai_match_type'] = 'Typ dopasowania';

// User feedback
$txt['sphinx_ai_feedback'] = 'Opinia';
$txt['sphinx_ai_helpful'] = 'Pomocne';
$txt['sphinx_ai_not_helpful'] = 'Niepomocne';
$txt['sphinx_ai_rate_result'] = 'Oceń ten wynik';
$txt['sphinx_ai_improve_search'] = 'Pomóż nam ulepszyć wyszukiwanie';

// Export and backup
$txt['sphinx_ai_export'] = 'Eksport';
$txt['sphinx_ai_backup'] = 'Kopia zapasowa';
$txt['sphinx_ai_restore'] = 'Przywróć';
$txt['sphinx_ai_import'] = 'Import';

// Security
$txt['sphinx_ai_security'] = 'Bezpieczeństwo';
$txt['sphinx_ai_access_control'] = 'Kontrola dostępu';
$txt['sphinx_ai_rate_limiting'] = 'Ograniczenie częstotliwości';
$txt['sphinx_ai_audit_log'] = 'Dziennik audytu';

// Updates and versioning
$txt['sphinx_ai_version'] = 'Wersja';
$txt['sphinx_ai_update_available'] = 'Dostępna aktualizacja';
$txt['sphinx_ai_check_updates'] = 'Sprawdź aktualizacje';
$txt['sphinx_ai_changelog'] = 'Lista zmian';

// Help and documentation
$txt['sphinx_ai_help'] = 'Pomoc';
$txt['sphinx_ai_documentation'] = 'Dokumentacja';
$txt['sphinx_ai_faq'] = 'FAQ';
$txt['sphinx_ai_support'] = 'Wsparcie';
$txt['sphinx_ai_contact'] = 'Kontakt';

// Time and date formats
$txt['sphinx_ai_just_now'] = 'właśnie teraz';
$txt['sphinx_ai_minutes_ago'] = '{count} minut temu';
$txt['sphinx_ai_hours_ago'] = '{count} godzin temu';
$txt['sphinx_ai_days_ago'] = '{count} dni temu';
$txt['sphinx_ai_weeks_ago'] = '{count} tygodni temu';
$txt['sphinx_ai_months_ago'] = '{count} miesięcy temu';

// File and data sizes
$txt['sphinx_ai_bytes'] = 'bajtów';
$txt['sphinx_ai_kb'] = 'KB';
$txt['sphinx_ai_mb'] = 'MB';
$txt['sphinx_ai_gb'] = 'GB';

// Status messages
$txt['sphinx_ai_status_online'] = 'Online';
$txt['sphinx_ai_status_offline'] = 'Offline';
$txt['sphinx_ai_status_maintenance'] = 'Konserwacja';
$txt['sphinx_ai_status_error'] = 'Błąd';
$txt['sphinx_ai_status_unknown'] = 'Nieznany';

// Search filters
$txt['sphinx_ai_filter_by'] = 'Filtruj według';
$txt['sphinx_ai_filter_date'] = 'Data';
$txt['sphinx_ai_filter_author'] = 'Autor';
$txt['sphinx_ai_filter_board'] = 'Dział';
$txt['sphinx_ai_filter_topic'] = 'Temat';
$txt['sphinx_ai_filter_clear'] = 'Wyczyść filtry';

// Sort options
$txt['sphinx_ai_sort_by'] = 'Sortuj według';
$txt['sphinx_ai_sort_relevance'] = 'Trafność';
$txt['sphinx_ai_sort_date'] = 'Data';
$txt['sphinx_ai_sort_author'] = 'Autor';
$txt['sphinx_ai_sort_replies'] = 'Odpowiedzi';
$txt['sphinx_ai_sort_views'] = 'Wyświetlenia';

// Pagination
$txt['sphinx_ai_page'] = 'Strona';
$txt['sphinx_ai_of'] = 'z';
$txt['sphinx_ai_results_per_page'] = 'Wyników na stronę';
$txt['sphinx_ai_show_more'] = 'Pokaż więcej';
$txt['sphinx_ai_show_less'] = 'Pokaż mniej';

// Mobile and responsive
$txt['sphinx_ai_mobile_search'] = 'Wyszukiwanie mobilne';
$txt['sphinx_ai_touch_friendly'] = 'Przyjazne dotykowi';
$txt['sphinx_ai_responsive'] = 'Responsywne';

// Accessibility
$txt['sphinx_ai_accessibility'] = 'Dostępność';
$txt['sphinx_ai_screen_reader'] = 'Czytnik ekranu';
$txt['sphinx_ai_keyboard_navigation'] = 'Nawigacja klawiaturą';
$txt['sphinx_ai_high_contrast'] = 'Wysoki kontrast';

// Customization
$txt['sphinx_ai_customize'] = 'Dostosuj';
$txt['sphinx_ai_theme'] = 'Motyw';
$txt['sphinx_ai_layout'] = 'Układ';
$txt['sphinx_ai_colors'] = 'Kolory';
$txt['sphinx_ai_fonts'] = 'Czcionki';

// Advanced search operators
$txt['sphinx_ai_operators'] = 'Operatory';
$txt['sphinx_ai_and'] = 'I';
$txt['sphinx_ai_or'] = 'LUB';
$txt['sphinx_ai_not'] = 'NIE';
$txt['sphinx_ai_exact_phrase'] = 'Dokładna fraza';
$txt['sphinx_ai_wildcard'] = 'Symbol wieloznaczny';

// Search history
$txt['sphinx_ai_history'] = 'Historia';
$txt['sphinx_ai_recent_searches'] = 'Ostatnie wyszukiwania';
$txt['sphinx_ai_saved_searches'] = 'Zapisane wyszukiwania';
$txt['sphinx_ai_clear_history'] = 'Wyczyść historię';

// Notifications
$txt['sphinx_ai_notifications'] = 'Powiadomienia';
$txt['sphinx_ai_new_results'] = 'Nowe wyniki';
$txt['sphinx_ai_search_alerts'] = 'Alerty wyszukiwania';
$txt['sphinx_ai_email_notifications'] = 'Powiadomienia e-mail';

// Social sharing
$txt['sphinx_ai_share'] = 'Udostępnij';
$txt['sphinx_ai_share_results'] = 'Udostępnij wyniki';
$txt['sphinx_ai_copy_link'] = 'Kopiuj link';
$txt['sphinx_ai_bookmark'] = 'Zakładka';

// Analytics and reporting
$txt['sphinx_ai_analytics'] = 'Analityka';
$txt['sphinx_ai_reports'] = 'Raporty';
$txt['sphinx_ai_insights'] = 'Spostrzeżenia';
$txt['sphinx_ai_trends'] = 'Trendy';
$txt['sphinx_ai_metrics'] = 'Metryki';
