<!-- footer.php: Modern Multi-Column KrazePlanet Footer with Socials & Contact -->
<style>
  .krazeplanet-footer {
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.95) 0%, rgba(7, 11, 20, 0.98) 100%);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    padding: 3.5rem 0 1.75rem 0;
    color: #94a3b8;
    font-size: 0.9rem;
    margin-top: auto;
    width: 100%;
    position: relative;
    backdrop-filter: blur(20px);
  }

  .footer-heading {
    color: #ffffff;
    font-size: 0.8rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    margin-bottom: 1.25rem;
    font-family: 'Outfit', sans-serif;
  }

  .footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
  }

  .footer-link {
    color: #94a3b8;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    font-size: 0.88rem;
  }

  .footer-link:hover {
    color: #38bdf8;
    transform: translateX(3px);
  }

  .footer-social-btn {
    width: 36px;
    height: 36px;
    background: rgba(15, 23, 42, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 10px;
    color: #94a3b8;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    text-decoration: none;
    transition: all 0.2s ease-in-out;
  }

  .footer-social-btn:hover {
    background: rgba(56, 189, 248, 0.15);
    border-color: #38bdf8;
    color: #38bdf8;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(56, 189, 248, 0.25);
  }

  /* Floating WhatsApp Action Button */
  .whatsapp-float-btn {
    position: fixed;
    bottom: 25px;
    right: 25px;
    width: 52px;
    height: 52px;
    background: #25D366;
    color: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
    box-shadow: 0 6px 20px rgba(37, 211, 102, 0.45);
    z-index: 999;
    text-decoration: none;
    transition: all 0.25s ease-in-out;
  }

  .whatsapp-float-btn:hover {
    background: #20ba59;
    color: #ffffff;
    transform: scale(1.08) translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 211, 102, 0.6);
  }

  .footer-bottom-bar {
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    margin-top: 2.5rem;
    padding-top: 1.5rem;
    text-align: center;
    color: #64748b;
    font-size: 0.82rem;
  }
</style>

<footer class="krazeplanet-footer">
  <div class="container">
    <div class="row g-4 justify-content-between">
      
      <!-- Brand & Description & Socials -->
      <div class="col-lg-3 col-md-6">
        <div class="d-flex align-items-center gap-2 mb-3">
          <img src="https://krazeplanet.com/favicon.png" alt="KrazePlanet Logo" style="height: 28px; width: 28px; object-fit: contain;">
          <span style="color: #ffffff; font-weight: 800; font-size: 1.25rem; letter-spacing: -0.5px; font-family: 'Outfit', sans-serif;">KrazePlanet</span>
        </div>
        <p class="text-secondary small mb-3" style="line-height: 1.6; font-size: 0.85rem;">
          Premium security tools and expert penetration testing services. Built for professionals who demand accuracy.
        </p>

        <!-- Social Links -->
        <div class="d-flex align-items-center gap-2">
          <!-- Instagram -->
          <a href="https://www.instagram.com/krazeplanetsecurity" target="_blank" rel="noopener noreferrer" class="footer-social-btn" title="Instagram">
            <i class="bi bi-instagram"></i>
          </a>
          <!-- Twitter / X -->
          <a href="https://x.com/rix4uni" target="_blank" rel="noopener noreferrer" class="footer-social-btn" title="X (Twitter)">
            <i class="bi bi-twitter"></i>
          </a>
          <!-- LinkedIn -->
          <a href="https://www.linkedin.com/company/KrazePlanet" target="_blank" rel="noopener noreferrer" class="footer-social-btn" title="LinkedIn">
            <i class="bi bi-linkedin"></i>
          </a>
          <!-- GitHub -->
          <a href="https://github.com/KrazePlanet" target="_blank" rel="noopener noreferrer" class="footer-social-btn" title="GitHub">
            <i class="bi bi-github"></i>
          </a>
        </div>
      </div>

      <!-- Tools -->
      <div class="col-lg-2 col-md-3 col-6">
        <h6 class="footer-heading">TOOLS</h6>
        <ul class="footer-links">
          <li><a href="https://store.krazeplanet.com/VulneraXSS" target="_blank" rel="noopener noreferrer" class="footer-link">VulneraXSS</a></li>
          <li><a href="https://store.krazeplanet.com/VulneraCrawl" target="_blank" rel="noopener noreferrer" class="footer-link">VulneraCrawl</a></li>
          <li><a href="https://store.krazeplanet.com/StackGuard" target="_blank" rel="noopener noreferrer" class="footer-link">StackGuard</a></li>
          <li><a href="https://store.krazeplanet.com/VyntraGuard" target="_blank" rel="noopener noreferrer" class="footer-link">VyntraGuard</a></li>
        </ul>
      </div>

      <!-- Services -->
      <div class="col-lg-2 col-md-3 col-6">
        <h6 class="footer-heading">SERVICES</h6>
        <ul class="footer-links">
          <li><a href="https://krazeplanet.com/services/website" target="_blank" rel="noopener noreferrer" class="footer-link">Web Pentesting</a></li>
          <li><a href="https://krazeplanet.com/services/api" target="_blank" rel="noopener noreferrer" class="footer-link">API Security</a></li>
          <li><a href="https://krazeplanet.com/services/mobile" target="_blank" rel="noopener noreferrer" class="footer-link">Mobile Testing</a></li>
          <li><a href="https://krazeplanet.com/services/source-code" target="_blank" rel="noopener noreferrer" class="footer-link">Code Review</a></li>
        </ul>
      </div>

      <!-- Company -->
      <div class="col-lg-2 col-md-3 col-6">
        <h6 class="footer-heading">COMPANY</h6>
        <ul class="footer-links">
          <li><a href="index.php" class="footer-link">Home</a></li>
          <li><a href="assignments.php" class="footer-link">Assignments</a></li>
          <li><a href="about.php" class="footer-link">About</a></li>
          <li><a href="https://academy.krazeplanet.com" target="_blank" rel="noopener noreferrer" class="footer-link">Courses</a></li>
        </ul>
      </div>

      <!-- Contact -->
      <div class="col-lg-3 col-md-3 col-6">
        <h6 class="footer-heading">CONTACT</h6>
        <ul class="footer-links">
          <li>
            <a href="mailto:contact@krazeplanet.com" class="footer-link">
              <i class="bi bi-envelope me-2 text-info"></i> contact@krazeplanet.com
            </a>
          </li>
          <li>
            <a href="https://wa.me/918527310670?text=Hi%20KrazePlanet%20team!%20I%20have%20an%20inquiry%20regarding%20your%20cybersecurity%20training%2C%20labs%2C%20and%20pentesting%20services." target="_blank" rel="noopener noreferrer" class="footer-link">
              <i class="bi bi-telephone me-2 text-success"></i> +91 8527310670
            </a>
          </li>
        </ul>
      </div>

    </div>

    <!-- Copyright -->
    <div class="footer-bottom-bar">
      &copy; 2026 KrazePlanet. All rights reserved.
    </div>
  </div>
</footer>

<!-- Floating WhatsApp Quick Connect Button with Pre-filled Inquiry Text -->
<a href="https://wa.me/918527310670?text=Hi%20KrazePlanet%20team!%20I%20have%20an%20inquiry%20regarding%20your%20cybersecurity%20training%2C%20labs%2C%20and%20pentesting%20services." target="_blank" rel="noopener noreferrer" class="whatsapp-float-btn" title="Chat with KrazePlanet on WhatsApp">
  <i class="bi bi-whatsapp"></i>
</a>
