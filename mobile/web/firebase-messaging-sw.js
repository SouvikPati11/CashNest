// FCM web background message service worker (task 2, web).
//
// Loaded automatically by firebase_messaging on the web. Replace the config
// placeholders below with the values from the Firebase console (web app), or
// generate this file from your build pipeline. Keep it at web/ root so the
// browser serves it from the site origin.

importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js');

firebase.initializeApp({
  apiKey: 'REPLACE_WITH_WEB_API_KEY',
  authDomain: 'REPLACE.firebaseapp.com',
  projectId: 'REPLACE',
  messagingSenderId: 'REPLACE',
  appId: 'REPLACE',
});

const messaging = firebase.messaging();

messaging.onBackgroundMessage((message) => {
  const title = (message.notification && message.notification.title) || 'CashNest';
  const options = {
    body: (message.notification && message.notification.body) || '',
    icon: '/icons/Icon-192.png',
    data: message.data || {},
  };
  self.registration.showNotification(title, options);
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const link = (event.notification.data && event.notification.data.deep_link) || '/';
  event.waitUntil(clients.openWindow(link));
});
