/*
  Requirement: Make the "Manage Weekly Breakdown" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="weeks-tbody"` to the <tbody> element
     inside your `weeks-table`.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the weekly data loaded from the JSON file.
let weeks = [];
let editingWeekId = null;

// --- Element Selections ---
// TODO: Select the week form ('#week-form').
// --- Element Selections ---
const weekForm = document.querySelector('#week-form');
const weeksTableBody = document.querySelector('#weeks-tbody');
const weekTitleInput = document.querySelector('#week-title');
const weekStartDateInput = document.querySelector('#week-start-date');
const weekDescriptionInput = document.querySelector('#week-description');
const weekLinksTextarea = document.querySelector('#week-links');
const addWeekButton = document.querySelector('#add-week');
// TODO: Select the weeks table body ('#weeks-tbody').

// --- Helper Functions ---
function getSanitizedLinks(text) {
  return text
    .split('\n')
    .map(link => link.trim())
    .filter(link => link.length > 0);
}

function resetFormState() {
  if (!weekForm) {
    return;
  }
  weekForm.reset();
  editingWeekId = null;
  if (addWeekButton) {
    addWeekButton.textContent = 'Add Week';
  }
}

function populateFormForEdit(week) {
  if (!weekForm) {
    return;
  }
  if (!weekTitleInput || !weekStartDateInput || !weekDescriptionInput || !weekLinksTextarea) {
    return;
  }
  weekTitleInput.value = week.title || '';
  weekStartDateInput.value = week.startDate || '';
  weekDescriptionInput.value = week.description || '';
  weekLinksTextarea.value = (week.links || []).join('\n');
  editingWeekId = week.id;
  if (addWeekButton) {
    addWeekButton.textContent = 'Update Week';
  }
  if (weekTitleInput) {
    weekTitleInput.focus();
  }
}

// --- Functions ---

/**
 * TODO: Implement the createWeekRow function.
 * It takes one week object {id, title, description}.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the `title`.
 * 2. A <td> for the `description`.
 * 3. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and `data-id="${id}"`.
 * - A "Delete" button with class "delete-btn" and `data-id="${id}"`.
 */
function createWeekRow(week) {
  // ... your implementation here ...
   const tr = document.createElement('tr');

  // Week title
  const titleTd = document.createElement('td');
  titleTd.textContent = week.title || 'Untitled Week';

  // Week description
  const descTd = document.createElement('td');
  descTd.textContent = week.description || 'No description provided.';

  // Actions (Edit + Delete)
  const actionsTd = document.createElement('td');

  const editBtn = document.createElement('button');
  editBtn.textContent = "Edit";
  editBtn.classList.add("edit-btn");
  editBtn.dataset.id = week.id;

  const deleteBtn = document.createElement('button');
  deleteBtn.textContent = "Delete";
  deleteBtn.classList.add("delete-btn");
  deleteBtn.dataset.id = week.id;

  actionsTd.appendChild(editBtn);
  actionsTd.appendChild(deleteBtn);

  // Add all tds into tr
  tr.appendChild(titleTd);
  tr.appendChild(descTd);
  tr.appendChild(actionsTd);

  return tr;
}

/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the `weeksTableBody`.
 * 2. Loop through the global `weeks` array.
 * 3. For each week, call `createWeekRow()`, and
 * append the resulting <tr> to `weeksTableBody`.
 */
function renderTable() {
  // ... your implementation here ...
  if (!weeksTableBody) {
    return;
  }

  // Clear existing rows
  weeksTableBody.innerHTML = '';

  if (!Array.isArray(weeks) || weeks.length === 0) {
    const emptyRow = document.createElement('tr');
    const emptyCell = document.createElement('td');
    emptyCell.colSpan = 3;
    emptyCell.textContent = 'No weeks available yet. Use the form above to add one.';
    emptyRow.appendChild(emptyCell);
    weeksTableBody.appendChild(emptyRow);
    return;
  }

  // Loop through weeks and create rows
  weeks.forEach(week => {
    const weekRow = createWeekRow(week);
    weeksTableBody.appendChild(weekRow);
  });
}

/**
 * TODO: Implement the handleAddWeek function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title, start date, and description inputs.
 * 3. Get the value from the 'week-links' textarea. Split this value
 * by newlines (`\n`) to create an array of link strings.
 * 4. Create a new week object with a unique ID (e.g., `id: \`week_${Date.now()}\``).
 * 5. Add this new week object to the global `weeks` array (in-memory only).
 * 6. Call `renderTable()` to refresh the list.
 * 7. Reset the form.
 */
function handleAddWeek(event) {
  // ... your implementation here ...
  event.preventDefault();

  if (!weekForm || !weekTitleInput || !weekStartDateInput || !weekDescriptionInput || !weekLinksTextarea) {
    return;
  }

  const title = weekTitleInput.value.trim();
  const startDate = weekStartDateInput.value;
  const description = weekDescriptionInput.value.trim();
  const links = getSanitizedLinks(weekLinksTextarea.value);

  if (!title || !startDate || !description) {
    return;
  }

  if (editingWeekId) {
    const index = weeks.findIndex(week => week.id === editingWeekId);
    if (index !== -1) {
      weeks[index] = {
        ...weeks[index],
        title,
        startDate,
        description,
        links
      };
    }
  } else {
    const newWeek = {
      id: `week_${Date.now()}`, // unique ID
      title,
      startDate,
      description,
      links
    };
    weeks.push(newWeek);
  }

  renderTable();
  resetFormState();
}

/**
 * TODO: Implement the handleTableClick function.
 * This is an event listener on the `weeksTableBody` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `weeks` array by filtering out the week
 * with the matching ID (in-memory only).
 * 4. Call `renderTable()` to refresh the list.
 */
function handleTableClick(event) {
  // ... your implementation here ...

    const target = event.target;

    if (!(target instanceof HTMLElement)) {
      return;
    }

    if (target.classList.contains("delete-btn")) {
    const id = target.dataset.id;

        weeks = weeks.filter(week => week.id !== id);

        renderTable();

        if (editingWeekId && editingWeekId === id) {
          resetFormState();
        }

    } else if (target.classList.contains('edit-btn')) {
      const id = target.dataset.id;
      const weekToEdit = weeks.find(week => week.id === id);
      if (weekToEdit) {
        populateFormForEdit(weekToEdit);
      }
    }

}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'weeks.json'.
 * 2. Parse the JSON response and store the result in the global `weeks` array.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to `weekForm` (calls `handleAddWeek`).
 * 5. Add the 'click' event listener to `weeksTableBody` (calls `handleTableClick`).
 */
async function loadAndInitialize() {
  // ... your implementation here ...

  try {
    const response = await fetch('weeks.json');
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    const data = await response.json();

    weeks = Array.isArray(data) ? data : [];

    renderTable();

    if (weekForm) {
      weekForm.addEventListener('submit', handleAddWeek);
    }

    if (weeksTableBody) {
      weeksTableBody.addEventListener('click', handleTableClick);
    }
  } catch (error) {
    console.error('Failed to load weeks.json:', error);
    renderTable();
    if (weekForm) {
      weekForm.addEventListener('submit', handleAddWeek);
    }
    if (weeksTableBody) {
      weeksTableBody.addEventListener('click', handleTableClick);
    }
    return;
  }

}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
