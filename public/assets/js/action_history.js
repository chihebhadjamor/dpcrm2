/**
 * Action History Modal Functionality
 *
 * This script handles the opening of the action history modal when clicking on an action ID.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Create the modal element if it doesn't exist
    if (!document.getElementById('action-history-modal')) {
        const modalHtml = `
            <div class="modal fade" id="action-history-modal" tabindex="-1" aria-labelledby="action-history-modal-label" aria-hidden="true">
                <div class="modal-dialog modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="action-history-modal-label">Action History</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="action-history-modal-content">
                            <div class="text-center">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p>Loading action history...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = modalHtml;
        document.body.appendChild(modalContainer.firstElementChild);
    }

    // Initialize the modal
    const actionHistoryModal = new bootstrap.Modal(document.getElementById('action-history-modal'));

    // Add click event listeners to action ID links
    function setupActionIdClickHandlers() {
        // Find all action ID links in tables
        const actionIdLinks = document.querySelectorAll('a.action-history-link');

        actionIdLinks.forEach(link => {
            // Remove any existing click event listeners
            link.removeEventListener('click', handleActionIdClick);
            // Add the click event listener
            link.addEventListener('click', handleActionIdClick);
        });
    }

    // Handler function for action ID link clicks
    function handleActionIdClick(e) {
        e.preventDefault(); // Prevent default navigation
        const actionId = this.dataset.actionId || this.textContent.trim();
        openActionHistoryModal(actionId);
    }

    // Function to open the action history modal
    function openActionHistoryModal(actionId) {
        const modalContent = document.getElementById('action-history-modal-content');

        // Show loading spinner
        modalContent.innerHTML = `
            <div class="text-center">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p>Loading action history...</p>
            </div>
        `;

        // Show the modal
        actionHistoryModal.show();

        // Fetch action history data
        fetch(`/actions/${actionId}/history`, {
            headers: {
                'Accept': 'text/html'
            }
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                // Update the modal content with the HTML response
                modalContent.innerHTML = html;

                // Update the modal title from the HTML content
                const modalTitle = document.getElementById('action-history-modal-label');
                const titleElement = modalContent.querySelector('.modal-title');
                if (modalTitle && titleElement) {
                    modalTitle.textContent = titleElement.textContent;
                }

                // Remove the duplicate modal header and footer if they exist
                const duplicateHeader = modalContent.querySelector('.modal-header');
                if (duplicateHeader) {
                    duplicateHeader.remove();
                }

                const duplicateFooter = modalContent.querySelector('.modal-footer');
                if (duplicateFooter) {
                    duplicateFooter.remove();
                }
            })
            .catch(error => {
                modalContent.innerHTML = `
                    <div class="alert alert-danger" role="alert">
                        Error loading action history: ${error.message}
                    </div>
                `;
            });
    }

    // Initial setup
    setupActionIdClickHandlers();

    // Setup for dynamically added content
    // This uses a MutationObserver to detect when new content is added to the page
    const observer = new MutationObserver(function(mutations) {
        let shouldSetupHandlers = false;

        mutations.forEach(function(mutation) {
            // Check for added nodes
            if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // ELEMENT_NODE
                        // Check if the node is or contains an action history link
                        if (node.classList && node.classList.contains('action-history-link') ||
                            node.querySelector && node.querySelector('.action-history-link')) {
                            shouldSetupHandlers = true;
                        }
                        // Also check for tables or table rows that might contain action links
                        if (node.tagName === 'TABLE' || node.tagName === 'TR' ||
                            node.querySelector && (node.querySelector('table') || node.querySelector('tr'))) {
                            shouldSetupHandlers = true;
                        }
                    }
                });
            }

            // Also check for attribute modifications that might add the action-history-link class
            if (mutation.type === 'attributes' &&
                mutation.attributeName === 'class' &&
                mutation.target.classList &&
                mutation.target.classList.contains('action-history-link')) {
                shouldSetupHandlers = true;
            }
        });

        // Only call setupActionIdClickHandlers once if needed
        if (shouldSetupHandlers) {
            setupActionIdClickHandlers();
        }
    });

    // Start observing the document with the configured parameters
    observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class'] });
});
