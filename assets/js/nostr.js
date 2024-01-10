// Create a new WebSocket connection to the specified URL
const socket = new WebSocket("ws://example.com");

class ZapListener {
  constructor(relayUrl, nostrPubkey, orderKey, onNewEvent) {
    this.relayUrl = relayUrl;
    this.nostrPubkey = nostrPubkey;
    this.orderKey = orderKey;
    this.onNewEvent = onNewEvent;
  }

  connect() {
    this.socket = new WebSocket(relayUrl);
    addListeners();
  }

  subscribe(nostrPubkey, orderKey) {
    this.socket.send(
      JSON.stringify([
        "REQ",
        [
          {
            kinds: [9735],
            authors: [nostrPubkey],
            "#e": [orderKey],
            limit: 1,
          },
        ],
      ])
    );
  }

  addListeners() {
    // Subscribe on open
    this.socket.addEventListener("open", () => {
      this.subscribe(nostrPubkey, orderKey);
    });

    // Listen for events
    this.socket.addEventListener("message", function (event) {
      console.log("Message from server:", event.data);

      // Example of processing received message
      const receivedData = JSON.parse(event.data);
      this.onNewEvent(receivedData);
    });

    // Reconnect on close
    this.socket.addEventListener("close", (event) => {
      alert("Disconnected");
      this.connect();
    });

    // Error handling
    socket.addEventListener("error", function (event) {
      console.error("WebSocket error:", event);
    });
  }
}
