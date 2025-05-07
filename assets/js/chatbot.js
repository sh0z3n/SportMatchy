document.addEventListener('DOMContentLoaded', function() {
    // Create chatbot elements
    const chatbot = document.createElement('div');
    chatbot.className = 'chatbot';
    chatbot.innerHTML = `
        <button class="chatbot-button">
            <i class="fas fa-comments"></i>
        </button>
        <div class="chatbot-window">
            <div class="chatbot-header">
                <h3>SportMatchy Assistant</h3>
                <button class="chatbot-close">&times;</button>
            </div>
            <div class="chatbot-messages"></div>
            <div class="chatbot-input">
                <input type="text" placeholder="Type your message...">
                <button class="chatbot-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(chatbot);

    // Chatbot elements
    const chatbotButton = chatbot.querySelector('.chatbot-button');
    const chatbotWindow = chatbot.querySelector('.chatbot-window');
    const chatbotClose = chatbot.querySelector('.chatbot-close');
    const chatbotMessages = chatbot.querySelector('.chatbot-messages');
    const chatbotInput = chatbot.querySelector('.chatbot-input input');
    const chatbotSend = chatbot.querySelector('.chatbot-send');

    // Welcome message
    const welcomeMessage = "Hey! I'm the bot of SportMatchy, made by Mokhtar and Abdelkrim for the web project. How can I help you today?";
    addMessage(welcomeMessage, 'bot');

    // Toggle chatbot window
    chatbotButton.addEventListener('click', () => {
        chatbotWindow.classList.toggle('active');
    });

    chatbotClose.addEventListener('click', () => {
        chatbotWindow.classList.remove('active');
    });

    // Send message
    function sendMessage() {
        const message = chatbotInput.value.trim();
        if (message) {
            // Add user message
            addMessage(message, 'user');
            chatbotInput.value = '';

            // Get bot response
            handleUserMessage(message);
        }
    }

    // Add message to chat
    function addMessage(text, sender) {
        const message = document.createElement('div');
        message.className = `message ${sender}`;
        message.innerHTML = `<div class="message-content">${text}</div>`;
        chatbotMessages.appendChild(message);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    }

    // Get bot response
    function handleUserMessage(message) {
        const lowerMessage = message.toLowerCase();
        
        // Predefined responses
        const responses = {
            'hello': 'Hello! How can I help you today?',
            'hi': 'Hi there! What can I do for you?',
            'help': 'I can help you with:\n- Finding sports events\n- Creating new events\n- Managing your profile\n- Answering questions about SportMatchy\nJust let me know what you need!',
            'event': 'To find events, you can:\n1. Visit the Events page\n2. Use the search filters\n3. Browse by sport category\nWould you like me to show you how to create an event?',
            'profile': 'To manage your profile:\n1. Click on your username in the top right\n2. Select "Profile" from the menu\n3. You can update your information there',
            'sport': 'We support various sports including:\n- Football\n- Basketball\n- Tennis\n- Swimming\n- And many more!',
            'contact': 'You can contact us through:\n- Email: contact@sportmatchy.com\n- Phone: +33 1 23 45 67 89\n- Visit our About page for more details',
            'thanks': 'You\'re welcome! Is there anything else I can help you with?',
            'bye': 'Goodbye! Have a great day!'
        };

        // Check for keywords in the message
        let response = null;
        for (const [keyword, reply] of Object.entries(responses)) {
            if (lowerMessage.includes(keyword)) {
                response = reply;
                break;
            }
        }

        // If no specific response found, use a default response
        if (!response) {
            const defaultResponses = [
                "I'm not sure I understand. Could you please rephrase that?",
                "I'm still learning! Could you try asking that differently?",
                "I'm not sure about that. Would you like to know about our events or sports instead?",
                "I'm here to help with SportMatchy. Would you like to know about our features?"
            ];
            response = defaultResponses[Math.floor(Math.random() * defaultResponses.length)];
        }

        // Add a small delay to make it feel more natural
        setTimeout(() => {
            addMessage(response, 'bot');
        }, 500);
    }

    // Event listeners for sending messages
    chatbotSend.addEventListener('click', sendMessage);
    chatbotInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    // Close chatbot when clicking outside
    document.addEventListener('click', (e) => {
        if (!chatbot.contains(e.target) && chatbotWindow.classList.contains('active')) {
            chatbotWindow.classList.remove('active');
            chatbotButton.classList.remove('active');
        }
    });
}); 