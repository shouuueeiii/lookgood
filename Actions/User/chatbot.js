const chatHTML = `
  <button id="chatbot-toggle">
<img src="/lookgood/New%20folder/Homepage/icon.png" width="35" alt="Chat">
  </button>

  <div id="chatbot-window">
    <div id="chatbot-header">
      <div class="chatbot-header-info">
        <div class="chatbot-avatar">LG</div>
        <div class="chatbot-header-text">
          <h2>LookGood Frames</h2>
          <p><span class="chatbot-status-dot"></span>Online</p>
        </div>
      </div>
    </div>

    <div id="chatbot-messages"></div>
    <div id="chatbot-suggestions"></div>

    <div id="chatbot-input-bar">
      <input id="chatbot-input" type="text" placeholder="Type here...">
      <button id="chatbot-send-btn">➤</button>
    </div>
  </div>
  `;

  document.body.insertAdjacentHTML("beforeend", chatHTML);

(function () {

  //keywordz and automated responses
  const data = [
    { keyword: ['product','frame', 'offer', 'sell', 'eyeglass'], a: "We offer a wide range of stylish eyeglass frames 👓 designed for both comfort and confidence. From minimalist and lightweight designs to bold statement frames, we have options that suit every personality and face shape." },
    { keyword: ['price','cost', 'how much', 'expensive', 'cheap'], a: "Our prices vary depending on the frame style and materials used. 💰 We offer both affordable everyday options and premium designs, so there's something for every budget." },
    { keyword: ['delivery','shipping', 'arrive', 'when', 'days'], a: "Delivery usually takes around 3–7 business days 🚚 depending on your location. Metro areas typically receive orders faster compared to provincial areas." },
    { keyword: ['cod','payment', 'cash', 'gcash', 'pay', 'maya'], a: "Yes! We offer Cash on Delivery (COD) for selected areas. 💵 You can also pay using GCash, Maya, or other available digital payment methods for your convenience." },
    { keyword: ['return','refund', 'exchange', 'damaged'], a: "We accept returns and exchanges for damaged or incorrect items within a limited period after delivery. 📦 Just make sure to contact our support team and provide proof for faster processing." },
    { keyword: ['hello','hi', 'holabels', 'hey', 'good morning'], a: "Hello! 👋 Welcome to LookGood Frames. I'm here to help you with anything about our products, pricing, or delivery. What would you like to know?" },
    { keyword: ['thanks', 'salamat', 'thank you'], a: "You’re welcome! 😊 Is there anything else I can help you with?" },
    { keyword: ['live agent', 'chat support', 'customer service', 'talk to agent', 'real person', 'human', 'agent', 'help me'], a: 'liveAgent' },
    { keyword: ['erica'], a: "aso pusa baboy daga ibon ahas baka pagong penguin dolphine tigre leon elepante buwaya unggoy! bulate kuto sidra dragon camel panda usa kwago kuneho ipis lamok toro kabayo bibe manok. giraffe langaw uod kambing garapata koala kalabaw pating itik kangaroo oso isda butiki bubuyog surot linta pokemon tipaklong tupa uwak kulisap octopus tuko bayawak starfish jellyfish langgam iguana. paniki gamogamo paruparo balyena" }
];

  // !keyword = default msg
  const fallback = "Sorry, I did not understand that.";

  const sug = [
    'Products?',
    'Prices?',
    'Delivery?',
    'Chat Support'
  ];

  //iliments
  const btn = document.getElementById('chatbot-toggle');
  const box = document.getElementById('chatbot-window');
  const msgs = document.getElementById('chatbot-messages');
  const input = document.getElementById('chatbot-input');
  const send = document.getElementById('chatbot-send-btn');
  const sugBox = document.getElementById('chatbot-suggestions');

  if (!btn || !box) return;

  let open = false;
  let chatSupportActive = false;
  let chatSupportMessageSent = false;
  let chatSupportAwaitingResolution = false;
  let resolutionPromptTimer = null;
  let resolutionPromptShown = false;
  let guestEmail = '';
  let guestName = 'Guest';
  let supportPollTimer = null;
  const seenServerMessageIds = new Set();
  const CHATBOT_BUILD = '20260411-support-ack-v3';
  const supportAckMessage = "Thank you for reaching out to LookGood Frames! 🙏 We've received your message and our support team will assist you shortly. You can also check our FAQ page for common answers.";
  const sessionUser = window.LG_CHAT_USER || null;
  const hasSessionIdentity = !!(
    sessionUser
    && sessionUser.isLoggedIn
    && String(sessionUser.role || '').toLowerCase() === 'user'
    && sessionUser.email
  );

  if (hasSessionIdentity) {
    guestEmail = String(sessionUser.email || '').trim();
    const fn = String(sessionUser.firstName || '').trim();
    const ln = String(sessionUser.lastName || '').trim();
    guestName = (fn + ' ' + ln).trim() || fn || 'Guest';
  }

  function getConversationStorageKey() {
    const identityKey = hasSessionIdentity
      ? `user_${String(sessionUser.userId || '').trim() || guestEmail}`
      : `guest_${guestEmail || 'anonymous'}`;
    return `lookgood_chat_history_${identityKey}`;
  }

  function getSupportStateStorageKey() {
    const identityKey = hasSessionIdentity
      ? `user_${String(sessionUser.userId || '').trim() || guestEmail}`
      : `guest_${guestEmail || 'anonymous'}`;
    return `lookgood_chat_support_active_${identityKey}`;
  }

  function applyBuildMigration() {
    const markerKey = `lookgood_chat_build_${hasSessionIdentity ? `user_${String(sessionUser.userId || '').trim() || guestEmail}` : `guest_${guestEmail || 'anonymous'}`}`;
    try {
      const lastBuild = localStorage.getItem(markerKey);
      if (lastBuild !== CHATBOT_BUILD) {
        if (hasSessionIdentity) {
          localStorage.removeItem(getConversationStorageKey());
          localStorage.removeItem(getSupportStateStorageKey());
        }
        localStorage.setItem(markerKey, CHATBOT_BUILD);
      }
    } catch (e) {
      // ignore storage issues
    }
  }

  function saveChatHistory() {
    if (!hasSessionIdentity) return;
    const history = [];
    const rows = msgs.querySelectorAll('.chat-message');
    rows.forEach((row) => {
      const bubble = row.querySelector('.msg-bubble');
      const timeEl = row.querySelector('.msg-time');
      if (!bubble || !timeEl) return;
      history.push({
        type: row.classList.contains('user') ? 'user' : 'bot',
        text: bubble.textContent || '',
        time: timeEl.textContent || '',
        messageId: row.dataset.messageId ? Number(row.dataset.messageId) : 0
      });
    });
    localStorage.setItem(getConversationStorageKey(), JSON.stringify(history));
  }

  function loadChatHistory() {
    if (!hasSessionIdentity) return false;
    try {
      const raw = localStorage.getItem(getConversationStorageKey());
      if (!raw) return false;
      const history = JSON.parse(raw);
      if (!Array.isArray(history) || history.length === 0) return false;

      msgs.innerHTML = '';
      history.forEach((entry) => {
        addMsg(entry.text || '', entry.type === 'user' ? 'user' : 'bot', entry.time || '', {
          skipSave: true,
          messageId: Number(entry.messageId || 0)
        });
      });
      return true;
    } catch (e) {
      return false;
    }
  }

  function clearResolutionPromptTimer() {
    if (resolutionPromptTimer) {
      clearTimeout(resolutionPromptTimer);
      resolutionPromptTimer = null;
    }
  }

  function removeResolutionPrompt() {
    const existing = document.getElementById('support-resolution-prompt');
    if (existing) existing.remove();
    resolutionPromptShown = false;
  }

  function isClosureMessage(text) {
    const value = String(text || '').toLowerCase();
    return /\b(ok|okay|okey|thank you|thanks|thank u|fixed|resolved|solved|all good|that's all|that is all|no more|not anymore)\b/.test(value);
  }

  function scheduleResolutionPrompt(delayMs) {
    if (!chatSupportActive) return;
    clearResolutionPromptTimer();
    removeResolutionPrompt();
    resolutionPromptTimer = setTimeout(() => {
      if (!chatSupportActive || resolutionPromptShown) return;
      addYesNoButtons();
      resolutionPromptShown = true;
      chatSupportAwaitingResolution = true;
    }, delayMs);
  }

  // functions
  function time() {
    return new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
  }

  function clean(t) {
    return t.toLowerCase();
  }

  function reply(txt) {
    let q = clean(txt);
    let best = '';

    for (let i = 0; i < data.length; i++) {
      let item = data[i];
      for (let j = 0; j < item.keyword.length; j++) {
        if (q.includes(item.keyword[j])) {
          best = item.a;
          break;
        }
      }
    }

    return best || fallback;
  }

  function addMsg(txt, type, explicitTime = '', opts = {}) {
    const wrap = document.createElement('div');
    wrap.className = 'chat-message ' + type;
    if (opts.messageId) {
      wrap.dataset.messageId = String(opts.messageId);
      seenServerMessageIds.add(Number(opts.messageId));
    }

    const avatar = document.createElement('div');
    avatar.className = 'msg-avatar';
    avatar.textContent = type === 'bot' ? 'LG' : 'AB';

    const bubble = document.createElement('div');
    bubble.className = 'msg-bubble';
    bubble.textContent = txt;

    const timeEl = document.createElement('div');
    timeEl.className = 'msg-time';
    timeEl.textContent = explicitTime || time();

    const col = document.createElement('div');
    col.className = 'msg-content';
    col.appendChild(bubble);
    col.appendChild(timeEl);

    if (type === 'bot') {
      wrap.appendChild(avatar);
      wrap.appendChild(col);
    } else {
      wrap.appendChild(col);
      wrap.appendChild(avatar);
    }

    msgs.appendChild(wrap);
    msgs.scrollTop = msgs.scrollHeight;
    if (!opts.skipSave) {
      saveChatHistory();
    }
  }

  function setChatSupportActive(active) {
    chatSupportActive = !!active;
    try {
      localStorage.setItem(getSupportStateStorageKey(), chatSupportActive ? '1' : '0');
    } catch (e) {
      // ignore storage issues
    }
    if (chatSupportActive && open) {
      startSupportPolling();
    } else if (!chatSupportActive) {
      stopSupportPolling();
      clearResolutionPromptTimer();
      removeResolutionPrompt();
    }
  }

  function stopSupportPolling() {
    if (supportPollTimer) {
      clearInterval(supportPollTimer);
      supportPollTimer = null;
    }
  }

  function startSupportPolling() {
    if (supportPollTimer || !guestEmail || !chatSupportActive) return;
    supportPollTimer = setInterval(fetchSupportMessages, 3000);
    fetchSupportMessages();
  }

  function fetchSupportMessages() {
    if (!guestEmail || !chatSupportActive) return;

    fetch('/lookgood/New%20folder/Checkout/chat-support.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        action: 'fetch_messages',
        user_id: hasSessionIdentity ? Number(sessionUser.userId || 0) : 0,
        email: guestEmail,
        name: guestName
      })
    })
    .then(response => response.json())
    .then(data => {
      if (!data.success || !Array.isArray(data.messages)) return;

      data.messages.forEach((msg) => {
        const msgId = Number(msg.id || 0);
        if (!msgId || seenServerMessageIds.has(msgId)) return;

        const senderType = String(msg.sender_type || '').toLowerCase();
        if (senderType === 'admin') {
          addMsg(String(msg.text || ''), 'bot', String(msg.time || ''), { messageId: msgId });
          if (chatSupportActive) {
            chatSupportAwaitingResolution = true;
            scheduleResolutionPrompt(15000);
          }
        } else {
          // Track user messages fetched from server to avoid duplicate rendering later.
          seenServerMessageIds.add(msgId);
        }
      });
    })
    .catch(() => {
      // keep silent during polling
    });
  }

  function addYesNoButtons() {
    clearResolutionPromptTimer();
    removeResolutionPrompt();

    const wrap = document.createElement('div');
    wrap.className = 'chat-message bot';
    wrap.id = 'support-resolution-prompt';

    const avatar = document.createElement('div');
    avatar.className = 'msg-avatar';
    avatar.textContent = 'LG';

    const bubble = document.createElement('div');
    bubble.className = 'msg-bubble live-agent-bubble';

    const text = document.createElement('p');
    text.className = 'live-agent-text';
    text.textContent = "Did this answer your question and fix your problem?";

    const yesBtn = document.createElement('button');
    yesBtn.className = 'live-agent-prompt-btn';
    yesBtn.style.marginBottom = '6px';
    yesBtn.innerHTML = `<span>✓</span> Yes, it's fixed!`;
    yesBtn.onclick = () => {
      clearResolutionPromptTimer();
      removeResolutionPrompt();
      yesBtn.disabled = true;
      noBtn.disabled = true;
      yesBtn.style.opacity = '0.5';
      noBtn.style.opacity = '0.5';
      chatSupportAwaitingResolution = false;
      chatSupportMessageSent = false;
      setChatSupportActive(false);
      addMsg('Yes, it\'s fixed!', 'user');
      setTimeout(() => {
        addMsg('Thank you so much for using LookGood Frames! 😊 We\'re glad we could help. If you have any future questions or concerns, feel free to reach out anytime. Happy shopping! 🛍️', 'bot');
      }, 600);
    };

    const noBtn = document.createElement('button');
    noBtn.className = 'live-agent-prompt-btn';
    noBtn.innerHTML = `<span>✕</span> No, still need help`;
    noBtn.onclick = () => {
      clearResolutionPromptTimer();
      removeResolutionPrompt();
      yesBtn.disabled = true;
      noBtn.disabled = true;
      yesBtn.style.opacity = '0.5';
      noBtn.style.opacity = '0.5';
      chatSupportAwaitingResolution = true;
      addMsg('No, still need help', 'user');
      setTimeout(() => {
        addMsg('No problem! 💪 One of our admin team members will get back to you shortly with more detailed assistance. Thank you for your patience!', 'bot');
        setTimeout(() => {
          chatSupportAwaitingResolution = false;
        }, 1200);
      }, 600);
    };

    bubble.appendChild(text);
    bubble.appendChild(yesBtn);
    bubble.appendChild(noBtn);

    const timeEl = document.createElement('div');
    timeEl.className = 'msg-time';
    timeEl.textContent = time();

    const col = document.createElement('div');
    col.className = 'msg-content';
    col.appendChild(bubble);
    col.appendChild(timeEl);

    wrap.appendChild(avatar);
    wrap.appendChild(col);

    msgs.appendChild(wrap);
    msgs.scrollTop = msgs.scrollHeight;
  }

  function typing() {
    const t = document.createElement('div');
    t.id = 'typing';
    t.className = 'chat-message bot';
    t.innerHTML = `
      <div class="msg-avatar">LG</div>
      <div class="typing-indicator">
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
      </div>
    `;
    msgs.appendChild(t);
    msgs.scrollTop = msgs.scrollHeight;
  }

  function stopTyping() {
    const t = document.getElementById('typing');
    if (t) t.remove();
  }

  // --- Chat Support bubble with button ---
  function addLiveAgentMsg() {
    const wrap = document.createElement('div');
    wrap.className = 'chat-message bot';

    const avatar = document.createElement('div');
    avatar.className = 'msg-avatar';
    avatar.textContent = 'LG';

    const bubble = document.createElement('div');
    bubble.className = 'msg-bubble live-agent-bubble';

    const text = document.createElement('p');
    text.className = 'live-agent-text';
    text.textContent = "Looks like you need more help on this. Would you like to chat with our support team?";

    const agentBtn = document.createElement('button');
    agentBtn.className = 'live-agent-prompt-btn';
    agentBtn.innerHTML = `<span class="live-agent-btn-icon">💬</span> Chat with Support`;
    agentBtn.onclick = () => {
      if (!hasSessionIdentity) {
        // Prompt for email and name only for guests
        const userEmail = prompt('Please enter your email address:', '');
        if (!userEmail || !userEmail.includes('@')) {
          addMsg('Please provide a valid email address to connect with support.', 'bot');
          return;
        }

        guestEmail = userEmail;
        const userName = prompt('What is your name?', 'Guest');
        if (userName) {
          guestName = userName;
        }
      }

      setChatSupportActive(true);
      chatSupportMessageSent = false;
      chatSupportAwaitingResolution = false;
      // Remove the prompt bubble
      wrap.remove();
      addMsg('You are connected to Chat Support. A member of our team will assist you shortly. How can we help? 🤝', 'bot');
    };

    bubble.appendChild(text);
    bubble.appendChild(agentBtn);

    const timeEl = document.createElement('div');
    timeEl.className = 'msg-time';
    timeEl.textContent = time();

    const col = document.createElement('div');
    col.className = 'msg-content';
    col.appendChild(bubble);
    col.appendChild(timeEl);

    wrap.appendChild(avatar);
    wrap.appendChild(col);

    msgs.appendChild(wrap);
    msgs.scrollTop = msgs.scrollHeight;
  }

  function sendMsg(text) {
    let val = (text || input.value).trim();
    if (!val) return;

    input.value = '';
    clearResolutionPromptTimer();
    removeResolutionPrompt();
    addMsg(val, 'user');

    // If in chat support mode and it's the first message
    if (chatSupportActive && !chatSupportMessageSent) {
      chatSupportMessageSent = true;
      
      // Send message to backend
      sendChatSupportMessage(val);
      
      setTimeout(() => {
        addMsg(supportAckMessage, 'bot');
      }, 600);

      // Only show the resolution prompt if the user's first message is itself a closing remark.
      if (isClosureMessage(val)) {
        scheduleResolutionPrompt(2000);
      }
      return;
    }

    if (chatSupportActive && chatSupportMessageSent) {
      sendChatSupportMessage(val);

      if (isClosureMessage(val)) {
        scheduleResolutionPrompt(2500);
      }
      return;
    }

    typing();

    setTimeout(() => {
      stopTyping();
      const ans = reply(val);
      if (ans === 'liveAgent') {
        addLiveAgentMsg();
      } else {
        addMsg(ans, 'bot');
      }
    }, 800);
  }

  function sendChatSupportMessage(messageText) {
    if (!guestEmail) {
      // Email not yet collected
      return;
    }

    fetch('/lookgood/New%20folder/Checkout/chat-support.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        action: 'send_message',
        user_id: hasSessionIdentity ? Number(sessionUser.userId || 0) : 0,
        email: guestEmail,
        name: guestName,
        message: messageText
      })
    })
    .then(response => response.json())
    .then(data => {
      if (!data.success) {
        console.error('Error sending message:', data.error);
        const backendError = String(data.error || 'Unable to send message right now.');
        if (backendError.toLowerCase().includes('admin account cannot be used as chat sender')) {
          addMsg('This account is currently an admin account, so chat support cannot send as a customer. Please log in using a user account.', 'bot');
        } else if (backendError.toLowerCase().includes('unable to resolve admin account')) {
          addMsg('Chat support is temporarily unavailable because no admin account is configured to receive messages yet.', 'bot');
        } else {
          addMsg('We could not send your message right now. Please try again in a moment.', 'bot');
        }
      }
    })
    .catch(error => {
      console.error('Error:', error);
      addMsg('Network error while sending your message. Please check your connection and try again.', 'bot');
    });
  }

  function buildSug() {
    sugBox.innerHTML = '';
    sug.forEach(s => {
      const b = document.createElement('button');
      b.className = 'suggestion-chip' + (s === 'Chat Support' ? ' live-agent-chip' : '');
      b.textContent = s === 'Chat Support' ? '💬 Chat Support' : s;
      b.onclick = () => {
        sendMsg(s);
        sugBox.innerHTML = '';
      };
      sugBox.appendChild(b);
    });
  }
  //events
  btn.onclick = () => {
    open = !open;
    box.classList.toggle('is-open');
    if (open && chatSupportActive) {
      startSupportPolling();
    } else if (!open) {
      stopSupportPolling();
    }
  };

  send.onclick = () => sendMsg();

  input.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      sendMsg();
    }
  });

  //default first chat ni bot
  setTimeout(() => {
    applyBuildMigration();
    const hasHistory = loadChatHistory();
    // Support mode should never auto-enable on load.
    chatSupportActive = false;
    chatSupportMessageSent = false;
    try {
      localStorage.setItem(getSupportStateStorageKey(), '0');
    } catch (e) {
      // ignore storage issues
    }

    if (!hasHistory) {
      addMsg('Halu! How can i help u?', 'bot');
    }
    buildSug();

    if (chatSupportActive) {
      startSupportPolling();
    }
  }, 400);

})();
