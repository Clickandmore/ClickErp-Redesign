# Locally served admin dependencies

Downloaded on 2026-09-10 from the URLs previously used by the shared admin layout. The layout now serves these files locally to avoid blocking page initialization on third-party requests.

- jQuery 3.7.1: https://code.jquery.com/jquery-3.7.1.min.js (MIT)
- DataTables 1.13.6 and Bootstrap 5 integration: https://cdn.datatables.net/1.13.6/ (MIT)
- DataTables Responsive 2.5.0: https://cdn.datatables.net/responsive/2.5.0/ (MIT)
- Font Awesome Free 6.5.1: https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/ (CSS: MIT; fonts: SIL OFL 1.1)
- SweetAlert2 11.26.25: https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js (MIT)
- Theme stylesheets and their fonts: see [theme-imports/SOURCES.md](theme-imports/SOURCES.md).

Keep upstream license headers when updating. Update local copies explicitly; these files do not auto-update with CDN releases.
