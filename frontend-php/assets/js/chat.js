/**
 * Chat functionality for the Legal AI application
 */
document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const chatForm = document.getElementById('chat-form');
    const messageInput = document.getElementById('message-input');
    const chatMessages = document.getElementById('chat-messages');
    
    // Keep track of conversation
    let conversationHistory = [];
    
    // Check if we have any saved messages
    if (localStorage.getItem('chat_history')) {
        try {
            const savedHistory = JSON.parse(localStorage.getItem('chat_history'));
            if (Array.isArray(savedHistory) && savedHistory.length > 0) {
                conversationHistory = savedHistory;
                renderChatHistory();
            }
        } catch (error) {
            console.error('Failed to load chat history:', error);
        }
    }
    
    // Event listeners
    chatForm?.addEventListener('submit', handleChatSubmit);
    
    // Handle chat form submission
    async function handleChatSubmit(e) {
        e.preventDefault();
        
        const userMessage = messageInput.value.trim();
        if (!userMessage) return;
        
        // Clear input
        messageInput.value = '';
        
        // Add user message to UI
        addMessageToChat('user', userMessage);
        
        // Add typing indicator
        const typingIndicator = addTypingIndicator();
        
        try {
            // In a real app, this would call your AI backend
            // For now, we'll simulate a response
            
            // Simulate network delay
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            // Remove typing indicator
            typingIndicator.remove();
            
            // Add bot response
            const response = "Thank you for your message. This is a placeholder response. In the actual application, this would be a response from the legal AI assistant.";
            addMessageToChat('assistant', response);
            
        } catch (error) {
            console.error('Error getting response:', error);
            typingIndicator.remove();
            addMessageToChat('system', 'Sorry, there was an error processing your request.');
        }
    }
    
    // Add a message to the chat
    function addMessageToChat(sender, content) {
        const messageElement = document.createElement('div');
        messageElement.className = `chat-message ${sender}-message`;
        
        let messageHTML = '';
        
        if (sender === 'user') {
            messageHTML = `
                <div class="message-content">
                    <p>${escapeHTML(content)}</p>
                </div>
            `;
        } else if (sender === 'assistant') {
            messageHTML = `
                <div class="message-content">
                    <p>${escapeHTML(content)}</p>
                </div>
            `;
        } else {
            // System message
            messageHTML = `
                <div class="message-content system">
                    <p>${escapeHTML(content)}</p>
                </div>
            `;
        }
        
        messageElement.innerHTML = messageHTML;
        chatMessages.appendChild(messageElement);
        
        // Save to history
        conversationHistory.push({ sender, content });
        localStorage.setItem('chat_history', JSON.stringify(conversationHistory));
        
        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    // Add typing indicator
    function addTypingIndicator() {
        const typingElement = document.createElement('div');
        typingElement.className = 'chat-message assistant-message typing';
        typingElement.innerHTML = `
            <div class="message-content">
                <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        `;
        chatMessages.appendChild(typingElement);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return typingElement;
    }
    
    // Render saved chat history
    function renderChatHistory() {
        conversationHistory.forEach(msg => {
            addMessageToChat(msg.sender, msg.content);
        });
    }
    
    // Helper function to escape HTML
    function escapeHTML(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}); 