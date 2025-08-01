/**
 * Filter Clear Buttons
 *
 * This script adds a clear button (x icon) to filter input fields
 * that appears when the field has content and allows users to clear
 * the filter with a single click.
 *
 * It also adds an Escape key shortcut to clear the filter when the
 * input is focused and the Escape key is pressed.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Apply to all filter inputs
    const filterInputs = document.querySelectorAll('.filter-input');

    filterInputs.forEach(input => {
        // Create a wrapper div with relative positioning
        const wrapper = document.createElement('div');
        wrapper.className = 'filter-input-wrapper';
        wrapper.style.position = 'relative';

        // Get the parent node of the input
        const parent = input.parentNode;

        // Replace the input with the wrapper
        parent.replaceChild(wrapper, input);

        // Add the input to the wrapper
        wrapper.appendChild(input);

        // Create the clear button
        const clearButton = document.createElement('span');
        clearButton.className = 'filter-clear-button';
        clearButton.innerHTML = '&times;'; // × symbol
        clearButton.style.position = 'absolute';
        clearButton.style.right = '10px';
        clearButton.style.top = '50%';
        clearButton.style.transform = 'translateY(-50%)';
        clearButton.style.cursor = 'pointer';
        clearButton.style.color = '#999';
        clearButton.style.fontSize = '16px';
        clearButton.style.display = 'none'; // Hidden by default
        clearButton.style.zIndex = '10';

        // Add the clear button to the wrapper
        wrapper.appendChild(clearButton);

        // Show/hide clear button based on input content
        function toggleClearButton() {
            if (input.value.trim() !== '') {
                clearButton.style.display = 'block';
            } else {
                clearButton.style.display = 'none';
            }
        }

        // Initial state
        toggleClearButton();

        // Function to clear the input and update the table
        function clearInput() {
            input.value = '';
            clearButton.style.display = 'none';

            // Trigger the input event to apply the filter
            const event = new Event('input', { bubbles: true });
            input.dispatchEvent(event);

            // For date inputs, also trigger change event
            if (input.type === 'date') {
                const changeEvent = new Event('change', { bubbles: true });
                input.dispatchEvent(changeEvent);
            }

            // Blur the input (lose focus) as per requirements
            input.blur();
        }

        // Add event listeners
        input.addEventListener('input', toggleClearButton);

        // Clear the input when the button is clicked
        clearButton.addEventListener('click', clearInput);

        // Add Escape key functionality
        input.addEventListener('keydown', function(e) {
            // Check if the Escape key was pressed
            if (e.key === 'Escape' || e.keyCode === 27) {
                // Only clear if there's content
                if (input.value.trim() !== '') {
                    clearInput();
                    // Prevent default Escape behavior
                    e.preventDefault();
                }
            }
        });
    });
});
