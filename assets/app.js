const messageInput = document.getElementById('message');
const submitBtn = document.getElementById('submit-btn');
const messagesEl = document.getElementById('messages');
const statusEl = document.getElementById('status');
const charCountEl = document.getElementById('char-count');

const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

const updateCharCount = () => {
    const length = messageInput.value.length;
    charCountEl.textContent = `${length} / 500`;
};

const showStatus = (message, isError = false) => {
    statusEl.textContent = message;
    statusEl.style.color = isError ? '#ff7b7b' : '#9aa3b2';
};

const renderMessages = (messages) => {
    messagesEl.innerHTML = '';

    messages.forEach((msg) => {
        const card = document.createElement('div');
        card.className = 'message';

        const content = document.createElement('p');
        content.textContent = msg.content;

        const footer = document.createElement('div');
        footer.className = 'message-footer';

        const date = document.createElement('span');
        date.textContent = msg.created_at;

        const likeBtn = document.createElement('button');
        likeBtn.className = 'like-btn';
        likeBtn.textContent = `Beğen (${msg.likes})`;
        if (msg.liked) {
            likeBtn.classList.add('liked');
        }

        likeBtn.addEventListener('click', async () => {
            if (msg.liked) {
                return;
            }
            likeBtn.disabled = true;
            const response = await fetch('/api/like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({ message_id: msg.id }),
            });

            const data = await response.json();
            if (data.success) {
                msg.likes = data.likes;
                msg.liked = true;
                likeBtn.textContent = `Beğen (${msg.likes})`;
                likeBtn.classList.add('liked');
            } else {
                showStatus(data.message || 'Beğeni alınamadı.', true);
            }
            likeBtn.disabled = false;
        });

        footer.appendChild(date);
        footer.appendChild(likeBtn);
        card.appendChild(content);
        card.appendChild(footer);
        messagesEl.appendChild(card);
    });
};

const loadMessages = async () => {
    const response = await fetch('/api/messages.php');
    const data = await response.json();
    if (data.success) {
        renderMessages(data.messages);
    }
};

submitBtn.addEventListener('click', async () => {
    const content = messageInput.value.trim();
    submitBtn.disabled = true;
    showStatus('Gönderiliyor...');

    const response = await fetch('/api/post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken,
        },
        body: JSON.stringify({ content }),
    });

    const data = await response.json();
    if (data.success) {
        messageInput.value = '';
        updateCharCount();
        showStatus('Mesaj gönderildi.');
        await loadMessages();
    } else {
        showStatus(data.message || 'Mesaj gönderilemedi.', true);
    }

    submitBtn.disabled = false;
});

messageInput.addEventListener('input', updateCharCount);

updateCharCount();
loadMessages();
