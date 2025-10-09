</main>

<footer class="footer-modern">
  <div class="footer-wave">
    <svg viewBox="0 0 1200 120" preserveAspectRatio="none">
      <path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" opacity=".25" class="wave-fill"></path>
      <path d="M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z" opacity=".5" class="wave-fill"></path>
      <path d="M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z" class="wave-fill"></path>
    </svg>
  </div>
  
  <div class="footer-content">
    <div class="container">
      <div class="row justify-content-center">
        <!-- Centered Brand Section -->
        <div class="col-lg-6 col-md-8">
          <div class="footer-section text-center">
            <div class="footer-brand justify-content-center">
              <div class="footer-brand-icon">
                <i class="bi bi-heart-pulse-fill"></i>
              </div>
              <h3>JDN CLINIC</h3>
            </div>
            <p class="footer-description">
              Providing exceptional JDN CLINIC solutions with cutting-edge technology and compassionate care.
            </p>
          </div>
        </div>
      </div>

      <!-- Bottom Bar -->
      <div class="footer-bottom">
        <div class="row align-items-center">
          <div class="col-md-6 text-center text-md-start">
            <p class="footer-copyright">
              © <?php echo date('Y'); ?> <span class="gradient-text">Lance Providing</span>. All rights reserved.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scroll to Top Button -->
  <button class="scroll-top" id="scrollTop" aria-label="Scroll to top">
    <i class="bi bi-arrow-up"></i>
  </button>
</footer>

<style>
/* Footer Styles */
.footer-modern {
  position: relative;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  color: #e2e8f0;
  margin-top: 5rem;
}

.footer-wave {
  position: absolute;
  top: -100px;
  left: 0;
  width: 100%;
  overflow: hidden;
  line-height: 0;
  pointer-events: none;
}

.footer-wave svg {
  position: relative;
  display: block;
  width: calc(100% + 1.3px);
  height: 100px;
}

.wave-fill {
  fill: #1e293b;
}

.footer-content {
  padding: 4rem 0 2rem;
}

/* Footer Brand */
.footer-brand {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.footer-brand-icon {
  width: 3rem;
  height: 3rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.footer-brand h3 {
  margin: 0;
  font-size: 1.75rem;
  font-weight: 800;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.footer-description {
  color: #94a3b8;
  font-size: 0.9375rem;
  line-height: 1.7;
  margin-bottom: 1.5rem;
}

/* Social Links */
.social-links {
  display: flex;
  gap: 0.75rem;
}

.social-link {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 0.75rem;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #e2e8f0;
  font-size: 1.125rem;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  text-decoration: none;
}

.social-link:hover {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  transform: translateY(-3px);
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
  border-color: transparent;
}

/* Footer Sections */
.footer-section {
  margin-bottom: 2rem;
}

.footer-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #f8fafc;
  margin-bottom: 1.5rem;
  position: relative;
  padding-bottom: 0.75rem;
}

.footer-title::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 3rem;
  height: 3px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 2px;
}

/* Footer Links */
.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-links li {
  margin-bottom: 0.75rem;
}

.footer-links a {
  color: #94a3b8;
  text-decoration: none;
  font-size: 0.9375rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s ease;
}

.footer-links a:hover {
  color: #667eea;
  transform: translateX(5px);
}

.footer-links a i {
  font-size: 0.75rem;
}

/* Contact Info */
.footer-contact {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-contact li {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  color: #94a3b8;
  font-size: 0.9375rem;
  line-height: 1.6;
}

.footer-contact i {
  color: #667eea;
  font-size: 1.125rem;
  flex-shrink: 0;
  margin-top: 0.125rem;
}

/* Footer Bottom */
.footer-bottom {
  margin-top: 3rem;
  padding-top: 2rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-copyright {
  margin: 0;
  color: #94a3b8;
  font-size: 0.9375rem;
}

.gradient-text {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  font-weight: 700;
}

.footer-bottom-links {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  gap: 1.5rem;
  justify-content: center;
}

.footer-bottom-links li {
  display: inline;
}

.footer-bottom-links a {
  color: #94a3b8;
  text-decoration: none;
  font-size: 0.9375rem;
  transition: all 0.3s ease;
}

.footer-bottom-links a:hover {
  color: #667eea;
}

/* Scroll to Top Button */
.scroll-top {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  width: 3rem;
  height: 3rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 0.75rem;
  font-size: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  opacity: 0;
  visibility: hidden;
  transform: translateY(20px);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
  z-index: 1000;
}

.scroll-top.visible {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.scroll-top:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
}

/* Responsive Design */
@media (max-width: 991.98px) {
  .footer-content {
    padding: 3rem 0 1.5rem;
  }

  .footer-bottom-links {
    margin-top: 1rem;
  }
}

@media (max-width: 767.98px) {
  .footer-wave {
    top: -50px;
  }

  .footer-wave svg {
    height: 50px;
  }

  .footer-bottom {
    text-align: center;
  }

  .footer-bottom-links {
    justify-content: center;
    flex-wrap: wrap;
  }

  .scroll-top {
    bottom: 1.5rem;
    right: 1.5rem;
    width: 2.75rem;
    height: 2.75rem;
  }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/script.js"></script>
<script>
// Scroll to Top Button Functionality
const scrollTopBtn = document.getElementById('scrollTop');

window.addEventListener('scroll', () => {
  if (window.scrollY > 300) {
    scrollTopBtn.classList.add('visible');
  } else {
    scrollTopBtn.classList.remove('visible');
  }
});

scrollTopBtn.addEventListener('click', () => {
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  });
});

// Add smooth reveal animation for footer sections
const observerOptions = {
  threshold: 0.1,
  rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = '1';
      entry.target.style.transform = 'translateY(0)';
    }
  });
}, observerOptions);

document.querySelectorAll('.footer-section').forEach((section) => {
  section.style.opacity = '0';
  section.style.transform = 'translateY(20px)';
  section.style.transition = 'all 0.6s ease-out';
  observer.observe(section);
});

// Dynamic year update
const yearElement = document.querySelector('.footer-copyright');
if (yearElement) {
  const currentYear = new Date().getFullYear();
  yearElement.innerHTML = yearElement.innerHTML.replace(/\d{4}/, currentYear);
}
</script>
</body>
</html>