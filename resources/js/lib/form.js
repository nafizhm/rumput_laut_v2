import http from "./http";

export function useForm(initialData = {}) {
    // Store initial data for reset functionality
    const defaults = JSON.parse(JSON.stringify(initialData));

    return {
        data: { ...initialData },
        errors: {},
        processing: false,
        recentlySuccessful: false,
        wasSuccessful: false,
        isDirty: false,

        /**
         * Reset form data to initial values
         * @param {Array|String|null} fields - Optional field(s) to reset. If null, reset all fields
         */
        reset(...fields) {
            if (fields.length === 0) {
                // Reset all fields to initial values
                this.data = JSON.parse(JSON.stringify(defaults));
                this.errors = {};
                this.isDirty = false;
            } else {
                // Reset specific fields only
                fields.forEach((field) => {
                    if (defaults.hasOwnProperty(field)) {
                        this.data[field] = JSON.parse(
                            JSON.stringify(defaults[field])
                        );
                        delete this.errors[field];
                    }
                });
            }
        },

        /**
         * Clear all errors or specific field errors
         * @param {Array|String|null} fields - Optional field(s) to clear errors for
         */
        clearErrors(...fields) {
            if (fields.length === 0) {
                this.errors = {};
            } else {
                fields.forEach((field) => {
                    delete this.errors[field];
                });
            }
        },

        /**
         * Set error for a specific field
         * @param {String|Object} field - Field name or object with field-error pairs
         * @param {String} message - Error message (if field is string)
         */
        setError(field, message = "") {
            if (typeof field === "object") {
                this.errors = { ...this.errors, ...field };
            } else {
                this.errors[field] = message;
            }
        },

        /**
         * Check if a specific field has an error
         * @param {String} field - Field name
         * @returns {Boolean}
         */
        hasErrors(field = null) {
            if (field) {
                return !!this.errors[field];
            }
            return Object.keys(this.errors).length > 0;
        },

        /**
         * Get error message for a field
         * @param {String} field - Field name
         * @returns {String}
         */
        getError(field) {
            const error = this.errors[field];
            if (Array.isArray(error)) {
                return error[0] || "";
            }
            return error || "";
        },

        /**
         * Transform data before submission
         * @param {Function} callback - Function to transform data
         * @returns {Object} - Transformed data
         */
        transform(callback) {
            return callback(this.data);
        },

        /**
         * Submit form data
         * @param {String} method - HTTP method
         * @param {String} url - Request URL
         * @param {Object} options - Additional options
         */
        async submit(method, url, options = {}) {
            this.processing = true;
            this.errors = {};
            this.recentlySuccessful = false;
            this.wasSuccessful = false;

            const data = options.transform
                ? options.transform(this.data)
                : this.data;

            try {
                const res = await http({
                    method,
                    url,
                    data: data,
                });

                this.recentlySuccessful = true;
                this.wasSuccessful = true;
                this.isDirty = false;

                // Auto-clear recentlySuccessful after 2 seconds (like Inertia)
                setTimeout(() => {
                    this.recentlySuccessful = false;
                }, 2000);

                // Call success callback if provided
                if (options.onSuccess) {
                    options.onSuccess(res);
                }

                return res;
            } catch (e) {
                this.wasSuccessful = false;

                // Handle validation errors
                if (e.response?.status === 422) {
                    this.errors = e.response.data.errors || {};
                }

                // Handle other errors with message
                if (e.response?.data?.message) {
                    console.error("Form error:", e.response.data.message);
                }

                // Call error callback if provided
                if (options.onError) {
                    options.onError(e);
                }

                throw e;
            } finally {
                this.processing = false;

                // Call finish callback if provided
                if (options.onFinish) {
                    options.onFinish();
                }
            }
        },

        post(url, options = {}) {
            return this.submit("post", url, options);
        },

        put(url, options = {}) {
            return this.submit("put", url, options);
        },

        patch(url, options = {}) {
            return this.submit("patch", url, options);
        },

        delete(url, options = {}) {
            return this.submit("delete", url, options);
        },
    };
}
