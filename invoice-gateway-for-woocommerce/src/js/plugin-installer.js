jQuery(document).ready(function ($) {
  const Swal = require("sweetalert2");

  // Define custom toast notification style
  const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
      toast.addEventListener("mouseenter", Swal.stopTimer);
      toast.addEventListener("mouseleave", Swal.resumeTimer);
    },
    customClass: {
      container: "igfw-swal-container",
      popup: "igfw-swal-popup",
      title: "igfw-swal-title",
      icon: "igfw-swal-icon",
    },
  });

  $(".plugin-installer").click(function (e) {
    e.preventDefault();

    const $button = $(this);
    const pluginSlug = $button.data("plugin-slug");
    const originalText = $button.text();
    const settings = window.igfw_plugin_installer;

    // Find the settings button
    const goToSettingsButton = $button.parent().find(".go-to-settings");

    // Disable button and show loading state
    $button.attr("disabled", "disabled").text(settings.installing_text);
    $button.prepend(
      '<span class="spinner is-active" style="float: none; margin-top: 0;"></span>'
    );

    $.ajax({
      url: settings.ajax_url,
      type: "POST",
      data: {
        action: "igfw_install_activate_plugin",
        plugin_slug: pluginSlug,
        nonce: settings.nonce,
        silent: true,
      },
      success: function (response) {
        // Make sure response is valid and properly structured.
        if (response && response.success) {
          // Show success message - using safe property access.
          Toast.fire({
            icon: "success",
            title:
              response.data && response.data.message
                ? response.data.message
                : "Plugin installed successfully!",
          });

          // If we have a redirect URL, go there.
          if (response.data && response.data.redirect_url) {
            window.location.href = response.data.redirect_url;
          } else {
            // Use direct methods instead of just classes.
            $button.hide();

            // Force the settings button to be visible using multiple techniques.
            goToSettingsButton
              .removeClass("hidden")
              .text(settings.go_to_settings_text || "Go to Settings")
              .css({
                display: "inline-block",
                visibility: "visible",
                opacity: 1,
              })
              .show();
          }
        } else {
          // Show error message with fallback.
          Toast.fire({
            icon: "error",
            title:
              response && response.data && response.data.message
                ? response.data.message
                : settings.install_error_message,
          });
          $button.text(originalText).removeAttr("disabled").show();
          goToSettingsButton.hide();
        }
      },
      error: function (error) {
        Toast.fire({
          icon: "error",
          title: settings.install_error_message,
        });
        $button.text(originalText).removeAttr("disabled").show();
        goToSettingsButton.hide();
      },
      complete: function () {
        // Remove the spinner
        $button.find(".spinner").remove();
      },
    });
  });
});
