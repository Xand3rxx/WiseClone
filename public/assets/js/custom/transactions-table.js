"use strict";

/**
 * Transactions Table Search and DataTable Initialization
 * This script handles the transactions table on the dashboard
 */
var KTTransactionsTable = function() {
    var table;
    var tableElement;

    // Initialize DataTable and search functionality
    var initTable = function() {
        tableElement = document.getElementById('kt_table_users');
        
        if (!tableElement) {
            console.log('Table element not found');
            return;
        }

        // Format date columns for proper sorting
        tableElement.querySelectorAll('tbody tr').forEach(function(row) {
            var cells = row.querySelectorAll('td');
            // Date is in the 9th column (index 8)
            if (cells.length > 8) {
                var dateCell = cells[8];
                var dateText = dateCell.innerText;
                if (dateText && typeof moment !== 'undefined') {
                    // Parse the date for sorting (format: "Month Day Year, time")
                    var parsedDate = moment(dateText, 'MMMM Do YYYY, h:mm:ssa');
                    if (parsedDate.isValid()) {
                        dateCell.setAttribute('data-order', parsedDate.format());
                    }
                }
            }
        });

        // Initialize DataTable
        table = $(tableElement).DataTable({
            info: true,
            order: [[8, 'desc']], // Sort by date column descending
            pageLength: 10,
            lengthChange: true,
            columnDefs: [
                { orderable: false, targets: 0 },  // # column
                { orderable: false, targets: 9 }   // Actions column
            ],
            language: {
                search: "",
                searchPlaceholder: "Search transactions...",
                lengthMenu: "Show _MENU_ entries",
                info: "Showing _START_ to _END_ of _TOTAL_ transactions",
                infoEmpty: "No transactions found",
                infoFiltered: "(filtered from _MAX_ total transactions)",
                emptyTable: "No transactions available",
                zeroRecords: "No matching transactions found"
            }
        });

        // Hook up the external search input
        var searchInput = document.querySelector('[data-kt-user-table-filter="search"]');
        if (searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                table.search(e.target.value).draw();
            });

            // Also handle paste events
            searchInput.addEventListener('paste', function(e) {
                setTimeout(function() {
                    table.search(searchInput.value).draw();
                }, 10);
            });

            // Clear search on input clear
            searchInput.addEventListener('search', function(e) {
                table.search(e.target.value).draw();
            });
        }
    };

    return {
        init: function() {
            initTable();
        }
    };
}();

// Initialize on DOM ready - use multiple fallback methods
if (typeof KTUtil !== 'undefined' && KTUtil.onDOMContentLoaded) {
    KTUtil.onDOMContentLoaded(function() {
        KTTransactionsTable.init();
    });
} else if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        KTTransactionsTable.init();
    });
} else {
    // DOM is already ready
    KTTransactionsTable.init();
}

