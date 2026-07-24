importScripts('https://www.gstatic.com/firebasejs/10.8.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.8.0/firebase-messaging-compat.js');

// We need to pass the config via URL query parameters or fetch it, 
// because SW doesn't have access to PHP variables.
// A simpler way: we'll use a dynamic endpoint or just rely on standard Web Push.
// Wait, Firebase messaging requires `firebase.initializeApp()` in the service worker too.
// If we can't hardcode it, we can fetch it. But let's just make the SW fetch the config from our API.
// Wait, we can fetch a config JSON file!
// Let's create a generic SW that fetches from /api/fcm_config.php

self.addEventListener('install', function(event) {
    self.skipWaiting();
});

let messaging = null;

// Initialize Firebase lazily when a push event arrives if not initialized
self.addEventListener('push', async (event) => {
    // If there is data in the event, let's extract it
    let data = {};
    if (event.data) {
        data = event.data.json();
    }
    
    // We don't strictly need to initialize Firebase app in the SW to show notifications
    // if the notification payload already contains everything.
    // The FCM backend HTTP v1 API actually sends WebPush standards that the browser handles automatically
    // if the payload has `notification`. But sometimes we need to handle data payload.
    
    const title = data.notification?.title || 'নতুন খবর';
    const options = {
        body: data.notification?.body || '',
        icon: data.notification?.image || '/assets/img/icon.png',
        image: data.notification?.image,
        data: data.fcmOptions || data.data || {}
    };
    
    if (data.notification) {
        event.waitUntil(
            self.registration.showNotification(title, options)
        );
    }
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    
    // Get the link from the payload
    let click_action = event.notification.data?.link || '/';
    
    event.waitUntil(
        clients.matchAll({ type: 'window' }).then(windowClients => {
            // Check if there is already a window/tab open with the target URL
            for (let i = 0; i < windowClients.length; i++) {
                let client = windowClients[i];
                // If so, just focus it.
                if (client.url === click_action && 'focus' in client) {
                    return client.focus();
                }
            }
            // If not, then open the target URL in a new window/tab.
            if (clients.openWindow) {
                return clients.openWindow(click_action);
            }
        })
    );
});
