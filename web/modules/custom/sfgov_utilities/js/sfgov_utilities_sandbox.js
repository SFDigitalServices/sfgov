// Add sandbox alert
const sandbox_alert = document.createElement("div");
sandbox_alert.classList.add('sandbox-alert');
sandbox_alert.innerHTML = `<p style="background: #c55236; color: #fff; padding: 10px; 
  margin: 0; font-size: 16px; font-weight: bold;">You are in the training version 
  of SF.gov. Your work will not be saved. To create live pages, go to 
  <a href="https://sf.gov" style="color: #fff; text-decoration: underline;">SF.gov</a></p>
  `;
document.body.prepend(sandbox_alert);
