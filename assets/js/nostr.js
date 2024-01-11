// Create a new WebSocket connection to the specified URL
class ZapListener {
  autoReconnect = true;

  constructor(relayUrl, nostrPubkey, orderKey, onNewEvent) {
    this.relayUrl = relayUrl;
    this.nostrPubkey = nostrPubkey;
    this.orderKey = orderKey;
    this.onNewEvent = onNewEvent.bind(this);
  }

  connect() {
    this.socket = new WebSocket(this.relayUrl);
    this.addListeners();
  }

  disconnect() {
    this.socket && this.socket.close();
  }

  subscribe(nostrPubkey, orderKey) {
    this.socket.send(
      JSON.stringify([
        "REQ",
        "subID",
        {
          kinds: [9735],
          authors: [nostrPubkey],
          "#e": [orderKey],
          limit: 1,
        },
      ])
    );
  }

  addListeners() {
    // Subscribe on open
    this.socket.addEventListener("open", () => {
      this.subscribe(this.nostrPubkey, this.orderKey);
    });

    // Listen for events
    this.socket.addEventListener("message", (message) => {
      console.log("Message from server:", message.data);

      const data = JSON.parse(message.data);
      console.dir(data);
      // message type
      switch (data[0]) {
        case "EOSE":
          this.onEOSE();
          return;
        case "EVENT":
          this.onNewEvent && this.onNewEvent(data[2]);
          return;
        default:
          console.error(`Unknown message type ${data[0]}`);
          console.error(message.data);
      }
    });

    // Reconnect on close
    this.socket.addEventListener("close", (event) => {
      console.info("Disconnected from relay");
      if (!this.autoReconnect) {
        return;
      }
      console.info("Reconnecting...");
      this.connect();
    });

    // Error handling
    this.socket.addEventListener("error", function (event) {
      console.error("WebSocket error:", event);
    });
  }

  onEOSE() {
    // this.autoReconnect = false;
    // this.disconnect();
  }
}
