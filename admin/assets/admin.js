document.addEventListener('DOMContentLoaded', () => {
  initAdminNav();
  initAdminTables();
});

function initAdminNav() {
  const nav = document.getElementById('adminNav');
  const btn = document.getElementById('adminMenuBtn');
  const backdrop = document.getElementById('adminBackdrop');
  if (!nav || !btn) return;

  function close() {
    nav.classList.remove('is-open');
    document.body.classList.remove('admin-nav-open');
    btn.setAttribute('aria-expanded', 'false');
    if (backdrop) backdrop.hidden = true;
  }

  function open() {
    nav.classList.add('is-open');
    document.body.classList.add('admin-nav-open');
    btn.setAttribute('aria-expanded', 'true');
    if (backdrop) backdrop.hidden = false;
  }

  btn.addEventListener('click', () => {
    nav.classList.contains('is-open') ? close() : open();
  });
  if (backdrop) backdrop.addEventListener('click', close);
  nav.querySelectorAll('a').forEach((a) => a.addEventListener('click', close));
  window.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
}

function initAdminTables() {
  if (typeof DataTable === 'undefined') return;

  document.querySelectorAll('table.admin-table').forEach((table) => {
    if (table.closest('.dt-container') || table.classList.contains('dataTable')) return;

    const isFormGrid = table.closest('form') && table.querySelector('input[type="checkbox"]');
    const lastCol = Math.max(0, table.querySelectorAll('thead th').length - 1);

    const options = isFormGrid
      ? {
          paging: false,
          searching: false,
          info: false,
          ordering: false,
          autoWidth: false,
        }
      : {
          pageLength: 10,
          lengthMenu: [10, 25, 50, 100],
          order: [],
          autoWidth: false,
          columnDefs: [{ orderable: false, targets: lastCol }],
          layout: {
            topStart: ['pageLength', { buttons: exportButtons() }],
            topEnd: 'search',
          },
          language: {
            search: 'Search:',
            lengthMenu: 'Show _MENU_',
            info: 'Showing _START_ to _END_ of _TOTAL_',
            infoEmpty: 'No records',
            emptyTable: 'No records yet.',
            zeroRecords: 'No matching records.',
          },
        };

    new DataTable(table, options);
  });
}

function exportTitle() {
  const h1 = document.querySelector('.admin-main h1');
  return (h1 && h1.textContent.trim()) || document.title;
}

function exportFilename() {
  return exportTitle().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'export';
}

function exportButtons() {
  const exportOptions = {
    columns: ':not(:last-child)',
    format: {
      body(data, _row, _col, node) {
        if (node) return (node.textContent || '').replace(/\s+/g, ' ').trim();
        return String(data).replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
      },
    },
  };

  return [
    { extend: 'copy',  text: 'Copy',  title: exportTitle, filename: exportFilename, exportOptions },
    { extend: 'csv',   text: 'CSV',   title: exportTitle, filename: exportFilename, exportOptions, bom: true },
    { extend: 'excel', text: 'Excel', title: exportTitle, filename: exportFilename, exportOptions },
    { extend: 'pdf',   text: 'PDF',   title: exportTitle, filename: exportFilename, exportOptions, orientation: 'landscape', pageSize: 'A4' },
    { extend: 'print', text: 'Print', title: exportTitle, exportOptions },
  ];
}
