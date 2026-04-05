/**
 * Sentinel Cameroon - API Communication Utilities
 * Handles all frontend-backend communication with proper error handling
 */

const API = {
  /**
   * Get base URL for API calls
   */
  baseUrl() {
    const protocol = window.location.protocol;
    const host = window.location.host;
    return `${protocol}//${host}`;
  },

  /**
   * Get CSRF token from page (if available)
   */
  getCsrfToken() {
    const token = document.querySelector('input[name="csrf_token"]')?.value;
    return token || "";
  },

  /**
   * Make API call with proper error handling
   */
  async call(endpoint, options = {}) {
    const url = endpoint.startsWith("/") ? endpoint : `/${endpoint}`;
    const fullUrl = this.baseUrl() + url;

    const fetchOptions = {
      method: options.method || "GET",
      headers: {
        "Content-Type": "application/json",
        ...options.headers,
      },
      credentials: "same-origin", // Send session cookies
      ...options,
    };

    // Add CSRF token for POST/PUT/DELETE/PATCH
    if (["POST", "PUT", "DELETE", "PATCH"].includes(fetchOptions.method)) {
      const csrfToken = this.getCsrfToken();
      if (csrfToken) {
        if (typeof fetchOptions.body === "string") {
          const data = JSON.parse(fetchOptions.body);
          data.csrf_token = csrfToken;
          fetchOptions.body = JSON.stringify(data);
        }
      }
    }

    try {
      const response = await fetch(fullUrl, fetchOptions);

      // Check HTTP status
      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      // Try to parse JSON
      let data;
      try {
        data = await response.json();
      } catch (e) {
        console.error("Invalid JSON response from:", fullUrl);
        throw new Error("Invalid server response");
      }

      return {
        success: true,
        data: data,
        status: response.status,
      };
    } catch (error) {
      console.error("API Error:", error);
      return {
        success: false,
        error: error.message,
        data: null,
      };
    }
  },

  /**
   * GET request
   */
  async get(endpoint, options = {}) {
    return this.call(endpoint, { method: "GET", ...options });
  },

  /**
   * POST request
   */
  async post(endpoint, body, options = {}) {
    return this.call(endpoint, {
      method: "POST",
      body: typeof body === "string" ? body : JSON.stringify(body),
      ...options,
    });
  },

  /**
   * PUT request
   */
  async put(endpoint, body, options = {}) {
    return this.call(endpoint, {
      method: "PUT",
      body: typeof body === "string" ? body : JSON.stringify(body),
      ...options,
    });
  },

  /**
   * DELETE request
   */
  async delete(endpoint, options = {}) {
    return this.call(endpoint, { method: "DELETE", ...options });
  },
};

// Example usage in templates:
/*
// Simple example
const result = await API.post('api/update_status.php', {
    incident_id: 1,
    status: 'resolved'
});

if (result.success) {
    console.log('Success:', result.data);
} else {
    console.error('Error:', result.error);
}
*/
