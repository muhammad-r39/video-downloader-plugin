jQuery(document).ready(function ($) {
  $("#vd-submit").click(function (e) {
    e.preventDefault();
    const url = $("#vd-url").val();
    if (!url) {
      alert("Please provide a video URL.");
      return;
    }

    $("#vd-result-container").html('<p class="loading">Loading...</p>');

    // Make AJAX call to get formats
    $.ajax({
      url: vd_ajax_object.ajax_url, // Make sure this is correct
      type: "POST",
      data: {
        action: "vd_get_video_formats",
        url: url,
      },
      success: function (response) {
        if (response.error) {
          $("#vd-result-container").html(
            '<p class="error">Error: ' + response.error + "</p>"
          );
          return;
        }

        // Extract data
        const { formats, title, thumbnail, platform } = response.data;

        // Display video metadata
        let html = `<div class="vd-response-head">
                        <h3>${title}</h3>
                        <p>Platform: <span class="platform">${platform}</span></p>
                    </div>`;

        html += `<div class="vd-results">
                    <div class="vd-thumbnail">`;

        if (thumbnail) {
          html += `<img src="${thumbnail}" alt="${title}" />`;
        }
        html += `</div>`;

        // Display download options
        html += `<div class="vd-formats-wrapper">
                    <h4 class="vd-formats-title">Available Options:</h4>`;

        if (formats && formats.length > 0) {
          html += `<ul class="vd-formats-list">`;
          formats.forEach((format) => {
            let size = format.filesize ? ` - (${format.filesize})` : "";
            html += `
                    <li>
                        ${format.resolution}
                        ${size}
                        <a class="vd-download-btn"
                            data-format-id="${format.format_id}"
                            href="${format.url}"
                            download
                            target="_blank">
                            Download
                            <svg fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 12V19M12 19L9.75 16.6667M12 19L14.25 16.6667M6.6 17.8333C4.61178 17.8333 3 16.1917 3 14.1667C3 12.498 4.09438 11.0897 5.59198 10.6457C5.65562 10.6268 5.7 10.5675 5.7 10.5C5.7 7.46243 8.11766 5 11.1 5C14.0823 5 16.5 7.46243 16.5 10.5C16.5 10.5582 16.5536 10.6014 16.6094 10.5887C16.8638 10.5306 17.1284 10.5 17.4 10.5C19.3882 10.5 21 12.1416 21 14.1667C21 16.1917 19.3882 17.8333 17.4 17.8333" stroke="#464455" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                    </li>`;
          });

          html += `</ul>`;
        } else {
          html = '<p class="error">No video found. Please check your URL.</p>';
        }

        html += `</div></div>`;

        $("#vd-result-container").html(html);
      },
      error: function () {
        $("#vd-result-container").html(
          '<p class="error">An error occurred while fetching video formats.</p>'
        );
      },
    });
  });
});