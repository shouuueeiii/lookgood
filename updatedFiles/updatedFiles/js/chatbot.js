const chatHTML = `
  <button id="chatbot-toggle">
<img src="/WST-copy/New%20folder/Homepage/icon.png" width="35">
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
    { keyword: ['product', 'frame', 'offer', 'sell', 'eyeglass'], a: "We offer a wide range of stylish eyeglass frames 👓 designed for both comfort and confidence. From minimalist and lightweight designs to bold statement frames, we have options that suit every personality and face shape." },
    { keyword: ['price', 'cost', 'how much', 'expensive', 'cheap'], a: "Our prices vary depending on the frame style and materials used. 💰 We offer both affordable everyday options and premium designs, so there's something for every budget." },
    { keyword: ['delivery', 'shipping', 'arrive', 'when', 'days'], a: "Delivery usually takes around 3–7 business days 🚚 depending on your location. Metro areas typically receive orders faster compared to provincial areas." },
    { keyword: ['cod', 'payment', 'cash', 'gcash', 'pay', 'maya'], a: "Yes! We offer Cash on Delivery (COD) for selected areas. 💵 You can also pay using GCash, Maya, or other available digital payment methods for your convenience." },
    { keyword: ['return', 'refund', 'exchange', 'damaged'], a: "We accept returns and exchanges for damaged or incorrect items within a limited period after delivery. 📦 Just make sure to contact our support team and provide proof for faster processing." },
    { keyword: ['hello', 'hi', 'holabels', 'hey', 'good morning'], a: "Hello! 👋 Welcome to LookGood Frames. I'm here to help you with anything about our products, pricing, or delivery. What would you like to know?" },
    { keyword: ['thanks', 'salamat', 'thank you'], a: "You’re welcome! 😊 Is there anything else I can help you with?" },
    { keyword: ['live agent', 'chat support', 'customer service', 'talk to agent', 'real person', 'human', 'agent', 'help me'], a: 'liveAgent' },
    { keyword: ['erica'], a: "aso pusa baboy daga ibon ahas baka pagong penguin dolphine tigre leon elepante buwaya unggoy! bulate kuto sidra dragon camel panda usa kwago kuneho ipis lamok toro kabayo bibe manok. giraffe langaw uod kambing garapata koala kalabaw pating itik kangaroo oso isda butiki bubuyog surot linta pokemon tipaklong tupa uwak kulisap octopus tuko bayawak starfish jellyfish langgam iguana. paniki gamogamo paruparo balyena" }
  ];

  // !keyword = default msg
  const fallback = "Sorry, I did not understand that.";

  const sug = [
    'Live Agent',
    'Products?',
    'Prices?',
    'Delivery?',
    'Mode of Payment',
    'Do you deliver outside Metro Manila?'
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

  // functions
  function time() {
    return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
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

  function addMsg(txt, type) {
    const wrap = document.createElement('div');
    wrap.className = 'chat-message ' + type;

    const avatar = document.createElement('div');
    avatar.className = 'msg-avatar';
    avatar.textContent = type === 'bot' ? 'LG' : 'AB';

    const bubble = document.createElement('div');
    bubble.className = 'msg-bubble';
    bubble.textContent = txt;

    const timeEl = document.createElement('div');
    timeEl.className = 'msg-time';
    timeEl.textContent = time();

    const col = document.createElement('div');
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

  // --- Live Agent bubble with button ---
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
    text.textContent = "Looks like you need more help on this. Would you like to chat with a live agent?";

    const agentBtn = document.createElement('button');
    agentBtn.className = 'live-agent-prompt-btn';
    agentBtn.innerHTML = `<span class="live-agent-btn-icon">💬</span> Chat with an Agent`;
    agentBtn.onclick = () => {
      agentBtn.disabled = true;
      agentBtn.style.opacity = '0.5';
      agentBtn.style.cursor = 'default';
      setTimeout(() => {
        addMsg('Connecting you to a live agent... please hold on. 🔄', 'bot');
        setTimeout(() => {
          addMsg('All our agents are currently busy. Your estimated wait time is a few minutes. We appreciate your patience! 🙏', 'bot');
        }, 2200);
      }, 600);
    };

    bubble.appendChild(text);
    bubble.appendChild(agentBtn);

    const timeEl = document.createElement('div');
    timeEl.className = 'msg-time';
    timeEl.textContent = time();

    const col = document.createElement('div');
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
    addMsg(val, 'user');

    typing();

    setTimeout(() => {
      stopTyping();
      const ans = reply(val);
      if (ans === 'liveAgent') {
        addLiveAgentMsg();
      } else {
        addMsg(ans, 'bot');
      }
      buildSug();   // rebuild suggestions after bot replies
    }, 800);
  }

  function buildSug() {
    sugBox.innerHTML = '';
    sug.forEach(s => {
      const b = document.createElement('button');
      b.className = 'suggestion-chip' + (s === 'Live Agent' ? ' live-agent-chip' : '');
      b.textContent = s === 'Live Agent' ? '🎧 Live Agent' : s;
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
    addMsg('Halu! How can i help u?', 'bot');
    buildSug();
  }, 400);

})();