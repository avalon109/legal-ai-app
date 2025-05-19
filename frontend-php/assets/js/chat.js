/**
 * Chat functionality for the Legal AI application
 * Version: 1.1.0 (force browser to reload)
 */

// Variables outside DOMContentLoaded to avoid reinitializing
let chatInitialized = false;
let chatForm = null;
let messageInput = null;
let chatMessages = null;
let conversationHistory = [];

// Function to clear chat history (exposed globally)
function clearChatHistory() {
    // Clear the in-memory conversation history
    conversationHistory = [];
    
    // Clear localStorage
    localStorage.removeItem('chat_history');
    
    // Clear DOM if chat container exists
    const chatContainer = document.getElementById('chat-messages');
    if (chatContainer) {
        chatContainer.innerHTML = '';
    }
    
    console.log('Chat history cleared completely');
}

// Make clearChatHistory available globally
window.clearChatHistory = clearChatHistory;

// Safe initialization function
function initChat() {
    // Prevent multiple initializations
    if (chatInitialized) return;
    chatInitialized = true;
    
    console.log('Initializing chat system');
    
    // DOM Elements
    chatForm = document.getElementById('chat-form');
    messageInput = document.getElementById('message-input');
    chatMessages = document.getElementById('chat-messages');
    
    // If elements don't exist, we're not on the chat page
    if (!chatForm || !messageInput || !chatMessages) {
        console.log('Not on chat page, skipping chat initialization');
        return;
    }
    
    // Load saved messages more safely
    try {
        const savedChatJSON = localStorage.getItem('chat_history');
        if (savedChatJSON) {
            const savedHistory = JSON.parse(savedChatJSON);
            if (Array.isArray(savedHistory) && savedHistory.length > 0) {
                // Cap the history size for performance (max 50 messages)
                conversationHistory = savedHistory.slice(-50);
                
                // Use a safer rendering approach that doesn't call addMessageToChat
                renderChatHistoryDirectly();
            }
        }
    } catch (error) {
        console.error('Failed to load chat history, starting fresh:', error);
        conversationHistory = [];
        localStorage.removeItem('chat_history');
    }
    
    // Event listeners
    chatForm?.addEventListener('submit', handleChatSubmit);
}

// Process initialization safely
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChat);
} else {
    initChat();
}

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
        // Call the real AI endpoint via server-side proxy
        const response = await fetchAIResponse(userMessage);
        
        // Remove typing indicator
        typingIndicator.remove();
        
        // Check if response is now an object with status
        if (typeof response === 'object' && response.status) {
            if (response.status === 'contract_needed') {
                // Show document upload UI or special instruction
                addMessageToChat('system', 'Please upload your rental contract to continue.');
                // You can trigger document upload UI here
            }
            addMessageToChat('assistant', response.text);
        } else {
            // Backward compatibility with string responses
            addMessageToChat('assistant', response);
        }
        
    } catch (error) {
        console.error('Error getting response:', error);
        typingIndicator.remove();
        addMessageToChat('system', 'Sorry, there was an error processing your request. ' + error.message);
    }
}

// Function to fetch response from AI endpoint via the proxy
async function fetchAIResponse(message) {
    // Create debugging info
    const debugInfo = {
        startTime: new Date().toISOString(),
        steps: [],
        errors: []
    };
    
    function logDebug(step, data = null) {
        console.log(`[${new Date().toISOString()}] ${step}`, data || '');
        debugInfo.steps.push({
            time: new Date().toISOString(),
            step: step,
            data: data
        });
    }
    
    try {
        logDebug("Starting AI request");
        
        // Show a message if the response takes more than 15 seconds
        const slowResponseTimeout = setTimeout(() => {
            logDebug("AI taking longer than usual to respond");
        }, 15000);
        
        // Using a relative URL to avoid any protocol issues
        const proxyUrl = './api/proxy-legal-ai.php';
        
        logDebug("Using server-side proxy", proxyUrl);
        logDebug("Sending question", { 
            length: message.length,
            preview: message.substring(0, 100) + (message.length > 100 ? '...' : '')
        });
        
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 150000); // 2.5 minute client-side timeout
        
        try {
            logDebug("Sending fetch request");
            const fetchStartTime = Date.now();
            
            const response = await fetch(proxyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    question: message
                }),
                cache: 'no-store',
                signal: controller.signal
            });
            
            const fetchEndTime = Date.now();
            logDebug(`Fetch completed in ${fetchEndTime - fetchStartTime}ms`, {
                status: response.status,
                statusText: response.statusText
            });
            
            clearTimeout(timeoutId);
            
            if (!response.ok) {
                logDebug("Response not OK", { 
                    status: response.status, 
                    statusText: response.statusText 
                });
                
                // Try to get more details from the error response
                try {
                    const errorResponse = await response.text();
                    logDebug("Error response text", errorResponse);
                    
                    try {
                        const errorJson = JSON.parse(errorResponse);
                        logDebug("Parsed error response", errorJson);
                        
                        if (response.status === 504) {
                            throw new Error("The AI took too long to respond. Please try a shorter question.");
                        } else {
                            throw new Error(errorJson.message || `Proxy responded with status: ${response.status}`);
                        }
                    } catch (jsonError) {
                        logDebug("Error parsing error response as JSON", jsonError);
                        throw new Error(`Proxy responded with status: ${response.status} - ${errorResponse.substring(0, 100)}`);
                    }
                } catch (textError) {
                    logDebug("Error getting error response text", textError);
                    throw new Error(`Proxy responded with status: ${response.status}`);
                }
            }
            
            logDebug("Starting to parse response JSON");
            const responseText = await response.text();
            logDebug("Raw response text received", {
                length: responseText.length,
                preview: responseText.substring(0, 200) + (responseText.length > 200 ? '...' : '')
            });
            
            let data;
            try {
                data = JSON.parse(responseText);
                logDebug("Successfully parsed response JSON", {
                    keys: Object.keys(data),
                    hasResponse: !!data.response,
                    responseType: data.response ? typeof data.response : null,
                    responseLength: data.response ? data.response.length : 0
                });
            } catch (parseError) {
                logDebug("Error parsing response JSON", parseError);
                // Try to sanitize and manually parse the response
                try {
                    // Try to clean the response by removing any non-printable characters
                    const sanitized = responseText.replace(/[\x00-\x1F\x7F-\x9F]/g, '');
                    data = JSON.parse(sanitized);
                    logDebug("Parsed sanitized JSON", data);
                } catch (e) {
                    logDebug("Failed to parse sanitized JSON", e);
                    throw new Error("Unable to parse AI response. The response was not valid JSON.");
                }
            }
            
            // Clear the slow response timeout
            clearTimeout(slowResponseTimeout);
            
            // Check if we have the expected 'response' field
            if (!data.response) {
                logDebug("Response missing expected 'response' field", data);
                
                // Check for the 'result' field which appears to be the actual field used by the API
                if (data.result) {
                    logDebug("Found 'result' field instead of 'response'", data.result);
                    // Return object with text and status if available
                    return {
                        text: data.result.replace(/\n/g, '<br>'),
                        status: data.status || 'normal'
                    };
                }
                // Check for the 'message' field which is sometimes used by the API
                else if (data.message) {
                    logDebug("Found 'message' field instead of 'response'", data.message);
                    return {
                        text: data.message.replace(/\n/g, '<br>'),
                        status: data.status || 'normal'
                    };
                }
                // Look for alternative fields that might contain the response
                else if (data.answer) {
                    logDebug("Found 'answer' field instead of 'response'", data.answer);
                    return {
                        text: data.answer.replace(/\n/g, '<br>'),
                        status: data.status || 'normal'
                    };
                } else if (data.text) {
                    logDebug("Found 'text' field instead of 'response'", data.text);
                    return {
                        text: data.text.replace(/\n/g, '<br>'),
                        status: data.status || 'normal'
                    };
                } else if (data.content) {
                    logDebug("Found 'content' field instead of 'response'", data.content);
                    return {
                        text: data.content.replace(/\n/g, '<br>'),
                        status: data.status || 'normal'
                    };
                } else if (data.debug_id) {
                    logDebug("Response contains debug_id", data.debug_id);
                    return `An error occurred. Please check with administrator. Debug ID: ${data.debug_id}`;
                } else if (typeof data === 'string') {
                    logDebug("Response is a plain string", data);
                    return data.replace(/\n/g, '<br>');
                }
                
                // If we get here, we couldn't find any usable response
                logDebug("No usable response field found in data", data);
                return 'No response received from the AI.';
            }
            
            logDebug("Valid response received from AI");
            // If we do have the response field, also convert newlines and check for status
            return {
                text: data.response.replace(/\n/g, '<br>'),
                status: data.status || 'normal'
            };
        } catch (fetchError) {
            clearTimeout(timeoutId);
            clearTimeout(slowResponseTimeout);
            
            logDebug("Fetch error", {
                name: fetchError.name,
                message: fetchError.message,
                stack: fetchError.stack
            });
            
            // Check if this was an abort error (timeout)
            if (fetchError.name === 'AbortError') {
                logDebug("Request aborted due to timeout");
                throw new Error("Request timed out. The AI is taking too long to respond.");
            }
            
            throw fetchError;
        }
    } catch (error) {
        console.error('AI API Error:', error);
        debugInfo.errors.push({
            time: new Date().toISOString(),
            message: error.message,
            stack: error.stack
        });
        
        // Log complete debug info
        console.log("Complete debug info:", debugInfo);
        
        throw new Error(error.message || 'Unable to connect to the AI service. Please try again later.');
    }
}

// Add a message to the chat
function addMessageToChat(sender, content, saveToHistory = true) {
    if (!chatMessages) return; // Safety check
    
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
    
    // Only save to history if the flag is true
    if (saveToHistory) {
        conversationHistory.push({ sender, content });
        localStorage.setItem('chat_history', JSON.stringify(conversationHistory));
    }
    
    // Scroll to bottom
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Add typing indicator
function addTypingIndicator() {
    if (!chatMessages) return document.createElement('div'); // Safety check with dummy return
    
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

// Directly render chat history to DOM without using addMessageToChat
function renderChatHistoryDirectly() {
    if (!chatMessages) return;
    
    // Clear the chat container first
    chatMessages.innerHTML = '';
    
    conversationHistory.forEach(msg => {
        const messageElement = document.createElement('div');
        messageElement.className = `chat-message ${msg.sender}-message`;
        
        let messageHTML = '';
        if (msg.sender === 'user') {
            messageHTML = `<div class="message-content"><p>${escapeHTML(msg.content)}</p></div>`;
        } else if (msg.sender === 'assistant') {
            messageHTML = `<div class="message-content"><p>${escapeHTML(msg.content)}</p></div>`;
        } else {
            // System message
            messageHTML = `<div class="message-content system"><p>${escapeHTML(msg.content)}</p></div>`;
        }
        
        messageElement.innerHTML = messageHTML;
        chatMessages.appendChild(messageElement);
    });
    
    // Scroll to bottom
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Helper function to escape HTML
function escapeHTML(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
} 