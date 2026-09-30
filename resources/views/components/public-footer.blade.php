<footer class="public-footer">

    <div class="public-footer__glow"></div>

    <div class="public-footer__inner">

        <div class="public-footer__brand">

            <span class="public-footer__eyebrow">
                AZ PORTFOLIO
            </span>

            <h2>
                Let's build something
                <span class="gradient-text">
                    interesting.
                </span>
            </h2>

            <p>
                Laravel, PHP, APIs, React, Symfony,
                Drupal, DevOps and modern web development.
            </p>

        </div>

        <div class="public-footer__links">

            <a href="tel:+14013383998">
                <span>Phone</span>
                <strong>
                    (401) 338-3998
                </strong>
            </a>

            <a href="mailto:fictionarts@gmail.com">
                <span>Email</span>
                <strong>
                    fictionarts@gmail.com
                </strong>
            </a>

            <a
                href="https://github.com/adrianzalewski88"
                target="_blank"
                rel="noopener noreferrer"
            >
                <span>GitHub</span>
                <strong>
                    adrianzalewski88
                </strong>
            </a>

            <a
                href="https://www.linkedin.com/in/adrian-zalewski-1988-fa/"
                target="_blank"
                rel="noopener noreferrer"
            >
                <span>LinkedIn</span>
                <strong>
                    Adrian Zalewski
                </strong>
            </a>

            <a href="{{ route('login') }}">
                <span>Private Area</span>
                <strong>
                    Login
                </strong>
            </a>

        </div>

    </div>

    <div class="public-footer__bottom">

        <span>
            © {{ date('Y') }} Adrian Zalewski
        </span>

        <span>
            Laravel - AZ Portfolio
        </span>

    </div>

</footer>