import axios from "axios";
const HOST = import.meta.env.VITE_BASE_URL as string;
const axiosInstance = axios.create({
  baseURL: HOST,
  timeout: 100000,
  headers: {
    "Content-Type": "application/json",
  },
});

axiosInstance.interceptors.request.use(
  (config) => {
    // Session-ID aus dem sessionStorage abrufen
    const sessionId = sessionStorage.getItem("sessionId");
    if (sessionId) {
      // Session-ID in den Headers mitgeben
      config.headers["X-Session-ID"] = sessionId;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  },
);
axiosInstance.interceptors.response.use(
  (response) => response, // Erfolgreiche Antwort zurückgeben
  async (error) => {
    if (error.response && error.response.data instanceof Blob) {
      try {
        // Versuche, den Blob als Text zu lesen
        const reader = new FileReader();
        const textPromise = new Promise<string>((resolve, reject) => {
          reader.onload = () => resolve(reader.result as string); // Typ-Assertion auf string
          reader.onerror = () => reject(reader.error);
        });
        reader.readAsText(error.response.data);

        const text = await textPromise;
        if (typeof text === "string") {
          const json = JSON.parse(text); // JSON-String parsen
          const isError = true;
          window.dispatchEvent(new CustomEvent("globalMessage", { detail: { isError, text: json.message } }));
          return Promise.reject(error); // Fehler weitergeben
        } else {
          throw new Error("Erwarteter Text ist kein String");
        }
      } catch (parseError) {
        console.error("Fehler beim Parsen der JSON-Antwort:", parseError);
      }
    } else if (error.response.data.message) {
      window.dispatchEvent(
        new CustomEvent("globalMessage", { detail: { isError: true, text: error.response.data.message } }),
      );
      return Promise.reject(error);
    }
    const text = "Fehler beim Verbindungsaufbau";
    const isError = true;
    window.dispatchEvent(new CustomEvent("globalMessage", { detail: { isError, text } }));
    return Promise.reject(new Error("Fehler beim Verbindungsaufbau"));
  },
);

export default axiosInstance;
