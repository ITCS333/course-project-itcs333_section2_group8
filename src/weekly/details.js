/*
  Requirement: Populate the weekly detail page and discussion forum.

  Instructions:
  1. Link this file to `details.html` using:
     <script src="details.js" defer></script>

  2. In `details.html`, add the following IDs:
     - To the <h1>: `id="week-title"`
     - To the start date <p>: `id="week-start-date"`
     - To the description <p>: `id="week-description"`
     - To the "Exercises & Resources" <ul>: `id="week-links-list"`
     - To the <div> for comments: `id="comment-list"`
     - To the "Ask a Question" <form>: `id="comment-form"`
     - To the <textarea>: `id="new-comment-text"`

  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// These will hold the data related to *this* specific week.
let currentWeekId = null;
let currentComments = [];

// --- Element Selections ---
const weekTitle = document.querySelector('#week-title');
const weekStartDate = document.querySelector('#week-start-date');
const weekDescription = document.querySelector('#week-description');
const weekLinksList = document.querySelector('#week-links-list');

const commentList = document.querySelector('#comment-list');
const commentForm = document.querySelector('#comment-form');
const newCommentText = document.querySelector('#new-comment-text');
// TODO: Select all the elements you added IDs for in step 2.

// --- Functions ---

/**
 * TODO: Implement the getWeekIdFromURL function.
 * It should:
 * 1. Get the query string from `window.location.search`.
 * 2. Use the `URLSearchParams` object to get the value of the 'id' parameter.
 * 3. Return the id.
 */
function getWeekIdFromURL() {
  // ... your implementation here ...
  const params = new URLSearchParams(window.location.search);
  return params.get('id');
}

/**
 * TODO: Implement the renderWeekDetails function.
 * It takes one week object.
 * It should:
 * 1. Set the `textContent` of `weekTitle` to the week's title.
 * 2. Set the `textContent` of `weekStartDate` to "Starts on: " + week's startDate.
 * 3. Set the `textContent` of `weekDescription`.
 * 4. Clear `weekLinksList` and then create and append `<li><a href="...">...</a></li>`
 * for each link in the week's 'links' array. The link's `href` and `textContent`
 * should both be the link URL.
 */
function renderWeekDetails(week) {
  // ... your implementation here ...
  if (!weekTitle || !weekStartDate || !weekDescription || !weekLinksList) {
    return;
  }

  weekTitle.textContent = week.title || "Week Details";

  weekStartDate.textContent = "Starts on: " + (week.startDate || "TBD");

  weekDescription.textContent = week.description || "More information will be added soon.";

  weekLinksList.innerHTML = "";

  const links = Array.isArray(week.links) ? week.links : [];

  if (links.length === 0) {
    const emptyItem = document.createElement('li');
    emptyItem.textContent = "No resources linked yet.";
    weekLinksList.appendChild(emptyItem);
    return;
  }

  links.forEach(link => {
    const li = document.createElement('li');
    const a = document.createElement('a');
    a.href = link;
    a.target = "_blank";
    a.rel = "noopener noreferrer";
    a.textContent = link;
    li.appendChild(a);
    weekLinksList.appendChild(li);
  });
}

/**
 * TODO: Implement the createCommentArticle function.
 * It takes one comment object {author, text}.
 * It should return an <article> element matching the structure in `details.html`.
 * (e.g., an <article> containing a <p> and a <footer>).
 */
function createCommentArticle(comment) {
  // ... your implementation here ...
  const article = document.createElement('article');
  article.classList.add('comment');

  const p = document.createElement('p');
  p.textContent = comment.text || '';

  const footer = document.createElement('footer');
  footer.textContent = "Posted by: " + (comment.author || 'Student');

  article.appendChild(p);
  article.appendChild(footer);

  return article;
}

/**
 * TODO: Implement the renderComments function.
 * It should:
 * 1. Clear the `commentList`.
 * 2. Loop through the global `currentComments` array.
 * 3. For each comment, call `createCommentArticle()`, and
 * append the resulting <article> to `commentList`.
 */
function renderComments() {
  // ... your implementation here ...
  if (!commentList) {
    return;
  }

  commentList.innerHTML = "";

  if (!Array.isArray(currentComments) || currentComments.length === 0) {
    const emptyState = document.createElement('p');
    emptyState.textContent = "No comments yet. Be the first to ask a question!";
    commentList.appendChild(emptyState);
    return;
  }

  currentComments.forEach(comment => {
    const commentArticle = createCommentArticle(comment);
    commentList.appendChild(commentArticle);
  });
}

/**
 * TODO: Implement the handleAddComment function.
 * This is the event handler for the `commentForm` 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the text from `newCommentText.value`.
 * 3. If the text is empty, return.
 * 4. Create a new comment object: { author: 'Student', text: commentText }
 * (For this exercise, 'Student' is a fine hardcoded author).
 * 5. Add the new comment to the global `currentComments` array (in-memory only).
 * 6. Call `renderComments()` to refresh the list.
 * 7. Clear the `newCommentText` textarea.
 */
function handleAddComment(event) {
  // ... your implementation here ...
  event.preventDefault(); 

  if (!newCommentText) {
    return;
  }

  const text = newCommentText.value.trim(); 

  if (text === "") return; 

  const newComment = {
    author: "Student",
    text: text
  };

  currentComments.push(newComment);

  renderComments();

  newCommentText.value = "";
}

/**
 * TODO: Implement an `initializePage` function.
 * This function needs to be 'async'.
 * It should:
 * 1. Get the `currentWeekId` by calling `getWeekIdFromURL()`.
 * 2. If no ID is found, set `weekTitle.textContent = "Week not found."` and stop.
 * 3. `fetch` both 'weeks.json' and 'week-comments.json' (you can use `Promise.all`).
 * 4. Parse both JSON responses.
 * 5. Find the correct week from the weeks array using the `currentWeekId`.
 * 6. Get the correct comments array from the comments object using the `currentWeekId`.
 * Store this in the global `currentComments` variable. (If no comments exist, use an empty array).
 * 7. If the week is found:
 * - Call `renderWeekDetails()` with the week object.
 * - Call `renderComments()` to show the initial comments.
 * - Add the 'submit' event listener to `commentForm` (calls `handleAddComment`).
 * 8. If the week is not found, display an error in `weekTitle`.
 */
async function initializePage() {
  // ... your implementation here ...
  currentWeekId = getWeekIdFromURL();

  // 2. If no ID → show error
  if (!currentWeekId) {
    if (weekTitle) {
      weekTitle.textContent = "Week not found.";
    }
    return;
  }

  try {
    // 3. Fetch both JSON files
    const [weeksRes, commentsRes] = await Promise.all([
      fetch("weeks.json"),
      fetch("week-comments.json")
    ]);

    // 4. Parse them
    if (!weeksRes.ok || !commentsRes.ok) {
      throw new Error("Failed to load week data.");
    }

    const weeksData = await weeksRes.json();
    const commentsData = await commentsRes.json();

    // 5. Find the correct week
    const week = weeksData.find(w => w.id === currentWeekId);

    if (!week) {
      if (weekTitle) {
        weekTitle.textContent = "Week not found.";
      }
      return;
    }

    // 6. Get comments or empty array
    currentComments = Array.isArray(commentsData[currentWeekId])
      ? commentsData[currentWeekId]
      : [];

    // 7. Render the page
    renderWeekDetails(week);
    renderComments();

    // 8. Add listener to the comment form
    if (commentForm) {
      commentForm.addEventListener("submit", handleAddComment);
    }

  } catch (error) {
    if (weekTitle) {
      weekTitle.textContent = "Error loading data.";
    }
    console.error(error);
  }
}

// --- Initial Page Load ---
initializePage();
