/*
  Requirement: Add interactivity and data management to the Admin Portal.

  Instructions:
  1. Link this file to your HTML using a <script> tag with the 'defer' attribute.
     Example: <script src="manage_users.js" defer></script>
  2. Implement the JavaScript functionality as described in the TODO comments.
  3. All data management will be done by manipulating the 'students' array
     and re-rendering the table.
*/

// Global students array
let students = [];

// TODO: Select the student table body (tbody).
const studentTableBody = document.querySelector('#student-table-body');

// TODO: Select the "Add Student" form.
// (You'll need to add id="add-student-form" to this form in your HTML).
const addStudentForm = document.querySelector('#add-student-form');

// TODO: Select the "Change Password" form.
// (You'll need to add id="password-form" to this form in your HTML).
const changePasswordForm = document.querySelector('#password-form');

// TODO: Select the search input field.
// (You'll need to add id="search-input" to this input in your HTML).
const searchInput = document.querySelector('#search-input');

// TODO: Select all table header (th) elements in thead.
const tableHeaders = document.querySelectorAll('#student-table thead th');

/**
 * TODO: Implement the createStudentRow function.
 * This function should take a student object {name, id, email} and return a <tr> element.
 * The <tr> should contain:
 * 1. A <td> for the student's name.
 * 2. A <td> for the student's ID.
 * 3. A <td> for the student's email.
 * 4. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and a data-id attribute set to the student's ID.
 * - A "Delete" button with class "delete-btn" and a data-id attribute set to the student's ID.
 */
function createStudentRow(student) {
    const tr = document.createElement('tr');
    
    // Create td for name
    const tdName = document.createElement('td');
    tdName.textContent = student.name;
    
    // Create td for id
    const tdId = document.createElement('td');
    tdId.textContent = student.id;
    
    // Create td for email
    const tdEmail = document.createElement('td');
    tdEmail.textContent = student.email;
    
    // Create td for actions
    const tdActions = document.createElement('td');
    
    // Create Edit button
    const editBtn = document.createElement('button');
    editBtn.textContent = 'Edit';
    editBtn.className = 'edit-btn';
    editBtn.setAttribute('data-id', student.id);
    
    // Create Delete button
    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = 'Delete';
    deleteBtn.className = 'delete-btn';
    deleteBtn.setAttribute('data-id', student.id);
    
    tdActions.appendChild(editBtn);
    tdActions.appendChild(deleteBtn);
    
    // Append all td elements to tr
    tr.appendChild(tdName);
    tr.appendChild(tdId);
    tr.appendChild(tdEmail);
    tr.appendChild(tdActions);
    
    return tr;
}

/**
 * TODO: Implement the renderTable function.
 * This function takes an array of student objects.
 * It should:
 * 1. Clear the current content of the `studentTableBody`.
 * 2. Loop through the provided array of students.
 * 3. For each student, call `createStudentRow` and append the returned <tr> to `studentTableBody`.
 */
function renderTable(studentsArray) {
    // 1. Clear the current content
    studentTableBody.innerHTML = '';
    
    // 2. Loop through the provided array
    // 3. For each student, call createStudentRow and append
    studentsArray.forEach(student => {
        const row = createStudentRow(student);
        studentTableBody.appendChild(row);
    });
}

/**
 * TODO: Implement the handleChangePassword function.
 * This function will be called when the "Update Password" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "current-password", "new-password", and "confirm-password" inputs.
 * 3. Perform validation:
 * - If "new-password" and "confirm-password" do not match, show an alert: "Passwords do not match."
 * - If "new-password" is less than 8 characters, show an alert: "Password must be at least 8 characters."
 * 4. If validation passes, show an alert: "Password updated successfully!"
 * 5. Clear all three password input fields.
 */
function handleChangePassword(event) {
    // 1. Prevent default submission
    event.preventDefault();
    
    // 2. Get values from inputs
    const currentPassword = document.querySelector('#current-password').value;
    const newPassword = document.querySelector('#new-password').value;
    const confirmPassword = document.querySelector('#confirm-password').value;
    
    // 3. Perform validation
    if (newPassword !== confirmPassword) {
        alert('Passwords do not match.');
        return;
    }
    
    if (newPassword.length < 8) {
        alert('Password must be at least 8 characters.');
        return;
    }
    
    // 4. If validation passes
    alert('Password updated successfully!');
    
    // 5. Clear all three password input fields
    document.querySelector('#current-password').value = '';
    document.querySelector('#new-password').value = '';
    document.querySelector('#confirm-password').value = '';
}

/**
 * TODO: Implement the handleAddStudent function.
 * This function will be called when the "Add Student" button is clicked.
 * It should:
 * 1. Prevent the form's default submission behavior.
 * 2. Get the values from "student-name", "student-id", and "student-email".
 * 3. Perform validation:
 * - If any of the three fields are empty, show an alert: "Please fill out all required fields."
 * - (Optional) Check if a student with the same ID already exists in the 'students' array.
 * 4. If validation passes:
 * - Create a new student object: { name, id, email }.
 * - Add the new student object to the global 'students' array.
 * - Call `renderTable(students)` to update the view.
 * 5. Clear the "student-name", "student-id", "student-email", and "default-password" input fields.
 */
function handleAddStudent(event) {
    // 1. Prevent default submission
    event.preventDefault();
    
    // 2. Get values from inputs
    const name = document.querySelector('#student-name').value.trim();
    const id = document.querySelector('#student-id').value.trim();
    const email = document.querySelector('#student-email').value.trim();
    const password = document.querySelector('#default-password').value.trim();
    
    // 3. Perform validation
    if (!name || !id || !email) {
        alert('Please fill out all required fields.');
        return;
    }
    
    // (Optional) Check if student with same ID exists
    const exists = students.some(student => student.id === id);
    if (exists) {
        alert('A student with this ID already exists.');
        return;
    }
    
    // 4. If validation passes
    // Create new student object
    const newStudent = { name, id, email };
    
    // Add to global students array
    students.push(newStudent);
    
    // Call renderTable to update view
    renderTable(students);
    
    // 5. Clear input fields
    document.querySelector('#student-name').value = '';
    document.querySelector('#student-id').value = '';
    document.querySelector('#student-email').value = '';
    document.querySelector('#default-password').value = 'password123';
}

/**
 * TODO: Implement the handleTableClick function.
 * This function will be an event listener on the `studentTableBody` (event delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it is a "delete-btn":
 * - Get the `data-id` attribute from the button.
 * - Update the global 'students' array by filtering out the student with the matching ID.
 * - Call `renderTable(students)` to update the view.
 * 3. (Optional) Check for "edit-btn" and implement edit logic.
 */
function handleTableClick(event) {
    // 1. Check if clicked element has class "delete-btn"
    if (event.target.classList.contains('delete-btn')) {
        // 2. Get the data-id attribute
        const studentId = event.target.getAttribute('data-id');
        
        // Update global students array by filtering out matching ID
        students = students.filter(student => student.id !== studentId);
        
        // Call renderTable to update view
        renderTable(students);
    }
    
    // 3. Handle edit button - Open modal popup
    if (event.target.classList.contains('edit-btn')) {
        const studentId = event.target.getAttribute('data-id');
        const student = students.find(s => s.id === studentId);
        
        if (student) {
            // Open the edit modal and populate with student data
            openEditModal(student);
        }
    }
}

/**
 * Open the edit modal and populate it with student data
 */
function openEditModal(student) {
    const modal = document.querySelector('#edit-modal');
    
    // Populate the modal form with student data
    document.querySelector('#edit-student-original-id').value = student.id;
    document.querySelector('#edit-student-name').value = student.name;
    document.querySelector('#edit-student-id').value = student.id;
    document.querySelector('#edit-student-email').value = student.email;
    
    // Show the modal
    modal.style.display = 'flex';
}

/**
 * Close the edit modal
 */
function closeEditModal() {
    const modal = document.querySelector('#edit-modal');
    modal.style.display = 'none';
    
    // Clear the form
    document.querySelector('#edit-student-original-id').value = '';
    document.querySelector('#edit-student-name').value = '';
    document.querySelector('#edit-student-id').value = '';
    document.querySelector('#edit-student-email').value = '';
}

/**
 * Handle the edit form submission
 */
function handleEditStudent(event) {
    event.preventDefault();
    
    // Get original ID and new values
    const originalId = document.querySelector('#edit-student-original-id').value;
    const newName = document.querySelector('#edit-student-name').value.trim();
    const newId = document.querySelector('#edit-student-id').value.trim();
    const newEmail = document.querySelector('#edit-student-email').value.trim();
    
    // Validate
    if (!newName || !newId || !newEmail) {
        alert('Please fill out all fields.');
        return;
    }
    
    // Check if new ID already exists (if ID was changed)
    if (newId !== originalId) {
        const idExists = students.some(s => s.id === newId);
        if (idExists) {
            alert('A student with this ID already exists.');
            return;
        }
    }
    
    // Find and update the student
    const studentIndex = students.findIndex(s => s.id === originalId);
    if (studentIndex !== -1) {
        students[studentIndex] = {
            name: newName,
            id: newId,
            email: newEmail
        };
        
        // Re-render the table
        renderTable(students);
        
        // Close the modal
        closeEditModal();
        
        alert('Student updated successfully!');
    }
}

/**
 * TODO: Implement the handleSearch function.
 * This function will be called on the "input" event of the `searchInput`.
 * It should:
 * 1. Get the search term from `searchInput.value` and convert it to lowercase.
 * 2. If the search term is empty, call `renderTable(students)` to show all students.
 * 3. If the search term is not empty:
 * - Filter the global 'students' array to find students whose name (lowercase)
 * includes the search term.
 * - Call `renderTable` with the *filtered array*.
 */
function handleSearch() {
    // 1. Get search term and convert to lowercase
    const searchTerm = searchInput.value.toLowerCase();
    
    // 2. If search term is empty, show all students
    if (!searchTerm) {
        renderTable(students);
        return;
    }
    
    // 3. Filter students whose name includes search term
    const filtered = students.filter(student => 
        student.name.toLowerCase().includes(searchTerm)
    );
    
    // Call renderTable with filtered array
    renderTable(filtered);
}

/**
 * TODO: Implement the handleSort function.
 * This function will be called when any `th` in the `thead` is clicked.
 * It should:
 * 1. Identify which column was clicked (e.g., `event.currentTarget.cellIndex`).
 * 2. Determine the property to sort by ('name', 'id', 'email') based on the index.
 * 3. Determine the sort direction. Use a data-attribute (e.g., `data-sort-dir="asc"`) on the `th`
 * to track the current direction. Toggle between "asc" and "desc".
 * 4. Sort the global 'students' array *in place* using `array.sort()`.
 * - For 'name' and 'email', use `localeCompare` for string comparison.
 * - For 'id', compare the values as numbers.
 * 5. Respect the sort direction (ascending or descending).
 * 6. After sorting, call `renderTable(students)` to update the view.
 */
function handleSort(event) {
    const th = event.currentTarget;
    
    // 1. Identify which column was clicked
    const columnIndex = th.cellIndex;
    
    // 2. Determine property to sort by
    let sortProperty;
    switch (columnIndex) {
        case 0:
            sortProperty = 'name';
            break;
        case 1:
            sortProperty = 'id';
            break;
        case 2:
            sortProperty = 'email';
            break;
        default:
            return; // Don't sort Actions column
    }
    
    // 3. Determine sort direction
    let sortDirection = th.getAttribute('data-sort-dir') || 'asc';
    sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
    th.setAttribute('data-sort-dir', sortDirection);
    
    // 4. Sort the global students array in place
    students.sort((a, b) => {
        let aValue = a[sortProperty];
        let bValue = b[sortProperty];
        
        let comparison;
        
        // For 'id', compare as numbers
        if (sortProperty === 'id') {
            comparison = parseInt(aValue) - parseInt(bValue);
        } else {
            // For 'name' and 'email', use localeCompare
            comparison = aValue.localeCompare(bValue);
        }
        
        // 5. Respect sort direction
        return sortDirection === 'asc' ? comparison : -comparison;
    });
    
    // 6. Call renderTable to update view
    renderTable(students);
}

/**
 * TODO: Implement the loadStudentsAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use the `fetch()` API to get data from 'students.json'.
 * 2. Check if the response is 'ok'. If not, log an error.
 * 3. Parse the JSON response (e.g., `await response.json()`).
 * 4. Assign the resulting array to the global 'students' variable.
 * 5. Call `renderTable(students)` to populate the table for the first time.
 * 6. After data is loaded, set up all the event listeners:
 * - "submit" on `changePasswordForm` -> `handleChangePassword`
 * - "submit" on `addStudentForm` -> `handleAddStudent`
 * - "click" on `studentTableBody` -> `handleTableClick`
 * - "input" on `searchInput` -> `handleSearch`
 * - "click" on each header in `tableHeaders` -> `handleSort`
 */
async function loadStudentsAndInitialize() {
    try {
        // 1. Use fetch() API to get data from API
        const response = await fetch('api/index.php?action=get_students');
        
        // 2. Check if response is ok
        if (!response.ok) {
            console.error('Failed to load students:', response.status);
            return;
        }
        
        // 3. Parse JSON response
        const data = await response.json();
        
        // 4. Assign to global students variable
        if (data.success && data.data) {
            students = data.data.map(s => ({
                name: s.name,
                id: s.email.split('@')[0],
                email: s.email
            }));
        } else {
            students = [];
        }
        
        // 5. Call renderTable to populate table
        renderTable(students);
        
    } catch (error) {
        console.error('Error loading students:', error);
        students = [];
        renderTable(students);
    }
    
    // 6. Set up all event listeners
    // "submit" on changePasswordForm -> handleChangePassword
    changePasswordForm.addEventListener('submit', handleChangePassword);
    
    // "submit" on addStudentForm -> handleAddStudent
    addStudentForm.addEventListener('submit', handleAddStudent);
    
    // "click" on studentTableBody -> handleTableClick
    studentTableBody.addEventListener('click', handleTableClick);
    
    // "input" on searchInput -> handleSearch
    searchInput.addEventListener('input', handleSearch);
    
    // "click" on each header in tableHeaders -> handleSort
    tableHeaders.forEach(th => {
        th.addEventListener('click', handleSort);
    });
    
    // Set up modal event listeners
    const editModal = document.querySelector('#edit-modal');
    const closeModalBtn = document.querySelector('#close-modal');
    const cancelEditBtn = document.querySelector('#cancel-edit');
    const editStudentForm = document.querySelector('#edit-student-form');
    
    // Close modal when X button is clicked
    closeModalBtn.addEventListener('click', closeEditModal);
    
    // Close modal when Cancel button is clicked
    cancelEditBtn.addEventListener('click', closeEditModal);
    
    // Handle edit form submission
    editStudentForm.addEventListener('submit', handleEditStudent);
    
    // Close modal when clicking outside the modal content
    editModal.addEventListener('click', function(event) {
        if (event.target === editModal) {
            closeEditModal();
        }
    });
    
    // Close modal when Escape key is pressed
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && editModal.style.display === 'flex') {
            closeEditModal();
        }
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', loadStudentsAndInitialize);
