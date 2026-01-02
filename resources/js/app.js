import "./bootstrap";

import Alpine from "alpinejs";
import persist from "@alpinejs/persist";

import { createIcons, icons } from "lucide";

Alpine.plugin(persist);

document.addEventListener("DOMContentLoaded", () => {
    createIcons({
        icons,
        attrs: {
            "stroke-width": 1.5,
        },
    });
});

document.addEventListener("alpine:initialized", () => {
    createIcons({
        icons,
        attrs: {
            "stroke-width": 1.5,
        },
    });
});

Alpine.store("toast", {
    toasts: [],
    add(toast) {
        const id = Date.now();
        this.toasts.push({
            id,
            type: toast.type || "info",
            title: toast.title || "",
            message: toast.message || "",
            timeout: toast.timeout || 3000,
            show: false,
        });

        setTimeout(() => {
            const index = this.toasts.findIndex((t) => t.id === id);
            if (index > -1) this.toasts[index].show = true;
        }, 10);

        setTimeout(() => {
            this.remove(id);
        }, toast.timeout || 3000);
    },
    remove(id) {
        const index = this.toasts.findIndex((t) => t.id === id);
        if (index > -1) {
            this.toasts[index].show = false;

            setTimeout(() => {
                this.toasts = this.toasts.filter((t) => t.id !== id);
            }, 300);
        }
    },
});

window.toast = {
    show(title = "", message, type = "info", timeout = 3000) {
        Alpine.store("toast").add({ type, title, message, timeout });
    },
    success(title = "Success", message, timeout = 3000) {
        this.show(title, message, "success", timeout);
    },
    error(title = "Error", message, timeout = 5000) {
        this.show(title, message, "error", timeout);
    },
    warning(title = "Warning", message, timeout = 4000) {
        this.show(title, message, "warning", timeout);
    },
    info(title = "Info", message, timeout = 3000) {
        this.show(title, message, "info", timeout);
    },
};

window.Alpine = Alpine;

Alpine.start();
