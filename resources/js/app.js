import "./bootstrap";

import Alpine from "alpinejs";
import persist from "@alpinejs/persist";
import collapse from "@alpinejs/collapse";

import { createIcons, icons } from "lucide";
import { useForm } from "./lib/form";
import { moneyInput } from "./lib/money-input";

window.useForm = useForm;
Alpine.data('moneyInput', moneyInput);

Alpine.plugin(persist);
Alpine.plugin(collapse);

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

window.getLocalDateInputValue = function () {
    const now = new Date();
    const timezoneOffset = now.getTimezoneOffset() * 60000;
    return new Date(now.getTime() - timezoneOffset).toISOString().split("T")[0];
};

window.getMonthStartDateInputValue = function () {
    const now = new Date();
    const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
    const timezoneOffset = firstDay.getTimezoneOffset() * 60000;
    return new Date(firstDay.getTime() - timezoneOffset)
        .toISOString()
        .split("T")[0];
};

window.get3MonthsAgoStartDateInputValue = function () {
    const now = new Date();
    const threeMonthsAgo = new Date(now.getFullYear(), now.getMonth() - 2, 1);
    const timezoneOffset = threeMonthsAgo.getTimezoneOffset() * 60000;
    return new Date(threeMonthsAgo.getTime() - timezoneOffset)
        .toISOString()
        .split("T")[0];
};

window.parseDateValue = function (value) {
    if (!value) return null;

    if (value instanceof Date) {
        return value;
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        return new Date(`${value}T00:00:00`);
    }

    return new Date(value);
};

window.formatDisplayDate = function (value, options = { dateStyle: "full" }) {
    const date = window.parseDateValue(value);
    if (!date || Number.isNaN(date.getTime())) {
        return "-";
    }

    return new Intl.DateTimeFormat("id-ID", options).format(date);
};

window.formatNumberInput = function (value, decimals = 2) {
    const number = Number(value || 0);
    return number.toLocaleString("id-ID", {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    });
};

window.formatWeight = function (value, decimals = 2) {
    const number = Number(value || 0);
    return `${number.toLocaleString("id-ID", {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
    })} Kg`;
};

window.formatCurrency = function (value) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(Number(value || 0));
};

window.Alpine = Alpine;

Alpine.start();
