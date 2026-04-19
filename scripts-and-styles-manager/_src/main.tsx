import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import App from "./App.tsx";

const rootElement = document.getElementById("scripts-and-styles-manager-root");
if (rootElement) {
  createRoot(
    document.getElementById("scripts-and-styles-manager-root")!
  ).render(
    <StrictMode>
      <App />
    </StrictMode>
  );
}
