/**
 * FILE: assets/js/app.js
 * Script Interaksi Sidebar Responsif & Inisialisasi DataTables SIMTS
 */
document.addEventListener('DOMContentLoaded', function () {
    const btnToggle = document.getElementById('btnToggleSidebar');
    const sidebar   = document.getElementById('simtsSidebar');
    const overlay   = document.getElementById('sidebarOverlay');

    function closeSidebar() {
        if (sidebar) sidebar.classList.remove('show');
        if (overlay) overlay.classList.remove('show');
    }

    if (btnToggle && sidebar && overlay) {
        btnToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
        overlay.addEventListener('click', closeSidebar);
    }

    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) {
            closeSidebar();
        }
    });

    // Inisialisasi DataTables Standar (.datatable-siata)
    if (window.jQuery && $.fn.DataTable) {
        $('.datatable-siata').each(function () {
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({
                    pageLength: 10,
                    language: {
                        search: "Cari:",
                        lengthMenu: "Tampilkan _MENU_ data",
                        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
                        infoEmpty: "Data tidak tersedia",
                        zeroRecords: "Data yang dicari tidak ditemukan",
                        paginate: {
                            first: "Awal",
                            last: "Akhir",
                            next: "&raquo;",
                            previous: "&laquo;"
                        }
                    }
                });
            }
        });

        // Inisialisasi DataTables dengan Tombol Export PDF / Excel / Print (.datatable-export)
        $('.datatable-export').each(function () {
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({
                    dom: "<'row align-items-center mb-3'<'col-md-6'B><'col-md-6'f>>" +
                         "<'row'<'col-12'tr>>" +
                         "<'row align-items-center mt-3'<'col-md-6'i><'col-md-6'p>>",
                    buttons: [
                        {
                            extend: 'excelHtml5',
                            text: '<i class="fa-solid fa-file-excel me-1"></i> Export Excel',
                            className: 'btn btn-success btn-sm me-1'
                        },
                        {
                            extend: 'pdfHtml5',
                            text: '<i class="fa-solid fa-file-pdf me-1"></i> Export PDF',
                            className: 'btn btn-danger btn-sm me-1',
                            orientation: 'landscape',
                            pageSize: 'A4'
                        },
                        {
                            extend: 'print',
                            text: '<i class="fa-solid fa-print me-1"></i> Cetak / Print',
                            className: 'btn btn-dark btn-sm'
                        }
                    ],
                    pageLength: 15,
                    language: {
                        search: "Cari Data:",
                        info: "Menampilkan _START_ - _END_ dari _TOTAL_ baris",
                        zeroRecords: "Data tidak ditemukan",
                        paginate: { next: "&raquo;", previous: "&laquo;" }
                    }
                });
            }
        });
    }
});
