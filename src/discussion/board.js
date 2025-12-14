/*
  Requirement: Make the "Discussion Board" page interactive.

  Instructions:
  1. Link this file to `board.html` (or `baord.html`) using:
     <script src="board.js" defer></script>
  
  2. In `board.html`, add an `id="topic-list-container"` to the 'div'
     that holds the list of topic articles.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the topics loaded from the JSON file.
let topics = [];

// --- Element Selections ---
// TODO: Select the new topic form ('#new-topic-form').
const newTopicForm = document.getElementById('new-topic-form');
const subjectInput = document.getElementById('topic-subject');
const messageInput = document.getElementById('topic-message');

// TODO: Select the topic list container ('#topic-list-container').
const topicListContainer = document.getElementById('topic-list-container');

// --- Functions ---

/**
 * TODO: Implement the createTopicArticle function.
 * It takes one topic object {id, subject, author, date}.
 * It should return an <article> element matching the structure in `board.html`.
 * - The main link's `href` MUST be `topic.html?id=${id}`.
 * - The footer should contain the author and date.
 * - The actions div should contain an "Edit" button and a "Delete" button.
 * - The "Delete" button should have a class "delete-btn" and `data-id="${id}"`.
 */
function createTopicArticle(topic) {
  const article = document.createElement('article');
  article.className = 'topic-card';

  const heading = document.createElement('h3');
  const link = document.createElement('a');
  link.href = `topic.html?id=${topic.id}`;
  link.textContent = topic.subject || 'Untitled Topic';
  heading.appendChild(link);

  const messageParagraph = document.createElement('p');
  messageParagraph.textContent = topic.message || 'No details provided yet.';

  const footer = document.createElement('footer');
  const author = topic.author || 'Student';
  const date = topic.date ? ` on ${topic.date}` : '';
  footer.textContent = `Posted by ${author}${date}`;

  const actions = document.createElement('div');
  actions.className = 'topic-actions';
  const editButton = document.createElement('button');
  editButton.type = 'button';
  editButton.className = 'edit-btn';
  editButton.textContent = 'Edit';
  const deleteButton = document.createElement('button');
  deleteButton.type = 'button';
  deleteButton.className = 'delete-btn';
  deleteButton.dataset.id = topic.id;
  deleteButton.textContent = 'Delete';

  actions.appendChild(editButton);
  actions.appendChild(deleteButton);

  article.appendChild(heading);
  article.appendChild(messageParagraph);
  article.appendChild(footer);
  article.appendChild(actions);

  return article;
}

/**
 * TODO: Implement the renderTopics function.
 * It should:
 * 1. Clear the `topicListContainer`.
 * 2. Loop through the global `topics` array.
 * 3. For each topic, call `createTopicArticle()`, and
 * append the resulting <article> to `topicListContainer`.
 */
function renderTopics() {
  if (!topicListContainer) return;
  topicListContainer.innerHTML = '';

  if (!topics.length) {
    const emptyState = document.createElement('p');
    emptyState.textContent = 'No topics yet. Be the first to start a discussion!';
    topicListContainer.appendChild(emptyState);
    return;
  }

  topics.forEach(topic => {
    const article = createTopicArticle(topic);
    topicListContainer.appendChild(article);
  });
}

/**
 * TODO: Implement the handleCreateTopic function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the '#topic-subject' and '#topic-message' inputs.
 * 3. Create a new topic object with the structure:
 * {
 * id: `topic_${Date.now()}`,
 * subject: (subject value),
 * message: (message value),
 * author: 'Student' (use a hardcoded author for this exercise),
 * date: new Date().toISOString().split('T')[0] // Gets today's date YYYY-MM-DD
 * }
 * 4. Add this new topic object to the global `topics` array (in-memory only).
 * 5. Call `renderTopics()` to refresh the list.
 * 6. Reset the form.
 */
function handleCreateTopic(event) {
  if (event && typeof event.preventDefault === 'function') {
    event.preventDefault();
  }

  if (!subjectInput || !messageInput) return;

  const subjectValue = subjectInput.value.trim();
  const messageValue = messageInput.value.trim();

  if (!subjectValue || !messageValue) {
    return;
  }

  const newTopic = {
    id: `topic_${Date.now()}`,
    subject: subjectValue,
    message: messageValue,
    author: 'Student',
    date: new Date().toISOString().split('T')[0]
  };

  topics = [...topics, newTopic];
  renderTopics();

  if (newTopicForm && typeof newTopicForm.reset === 'function') {
    newTopicForm.reset();
  }
}

/**
 * TODO: Implement the handleTopicListClick function.
 * This is an event listener on the `topicListContainer` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `topics` array by filtering out the topic
 * with the matching ID (in-memory only).
 * 4. Call `renderTopics()` to refresh the list.
 */
function handleTopicListClick(event) {
  const target = event.target;
  if (!target || !target.classList.contains('delete-btn')) {
    return;
  }

  const topicId = target.getAttribute('data-id');
  if (!topicId) return;

  topics = topics.filter(topic => topic.id !== topicId);
  renderTopics();
}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'topics.json'.
 * 2. Parse the JSON response and store the result in the global `topics` array.
 * 3. Call `renderTopics()` to populate the list for the first time.
 * 4. Add the 'submit' event listener to `newTopicForm` (calls `handleCreateTopic`).
 * 5. Add the 'click' event listener to `topicListContainer` (calls `handleTopicListClick`).
 */
async function loadAndInitialize() {
  try {
    const response = await fetch('api/topics.json');
    if (!response.ok) {
      throw new Error(`Failed to load topics: ${response.status}`);
    }
    const data = await response.json();
    topics = Array.isArray(data) ? data : [];
  } catch (error) {
    console.error('Unable to load topics.', error);
    topics = [];
  }

  renderTopics();

  if (newTopicForm) {
    newTopicForm.addEventListener('submit', handleCreateTopic);
  }

  if (topicListContainer) {
    topicListContainer.addEventListener('click', handleTopicListClick);
  }
}

// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
