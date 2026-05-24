# Our Engineering Manifesto

This document is more than just a set of rules; it's our manifesto. It defines who we are as engineers, what we value, and how we build software. It is a living document that evolves with us, but its core principles remain our guiding light.

We believe in **craftsmanship**, **pragmatism**, and **continuous improvement**. We build software that is not only functional but also elegant, maintainable, and a joy to work on.

## Security First: A Non-Negotiable Principle

Before any line of code is written, we acknowledge that security is our highest priority. It is not a feature or an afterthought; it is the foundation upon which we build everything.

*   **No Hardcoded Secrets:** We have a zero-tolerance policy for sensitive data (API keys, passwords, tokens) in our codebase. Such credentials must never be committed to version control.
*   **Configuration via Environment:** All configuration, especially secrets, must be loaded from environment variables (e.g., via `.env` files). We ensure that our `.gitignore` files are always configured to exclude these files.
*   **Data Masking:** We are vigilant about protecting data in logs and error reports. All sensitive information must be masked or omitted to prevent accidental exposure.

## Core Philosophy

Our philosophy is built on a foundation of proven principles that ensure the quality, longevity, and robustness of our code.

*   **SOLID Principles & The "AND" Test:** We strictly adhere to SOLID principles, especially the Single Responsibility Principle (SRP). A simple test for this: if you describe what a class or method does and need to use the word "AND," it's a sign it's doing too much and must be split.
    *   *Example:* A service that "validates input AND sends an email" should be two separate services. A method that "hashes a password AND logs the attempt" should be two methods.

*   **The Art of Simplicity: A Personal Approach:** We believe the best code is the code that is easiest to delete. Simplicity and readability are paramount. To achieve this, we follow a few pragmatic heuristics:
    *   **"One Glance" Rule:** Methods should be short and focused enough to be understood in a single glance, without needing to scroll.
    *   **Readable Conditionals:** Complex `if` statements are a code smell. We refactor them by extracting the logic into well-named boolean variables or dedicated methods, making the condition read like a sentence.
    *   **The "AND" Test (revisited):** This applies to all levels. If a class or method's purpose contains "and", it's a red flag.

    *A Personal Note:* These are our current best practices for simplifying code. They are not dogma. If you can demonstrate a better, cleaner, or more efficient way to achieve the same result, we are not just open to it—we are eager to learn and adopt it. This is what continuous improvement means to us.

*   **DRY (Don't Repeat Yourself):** We value knowledge and its clear, unambiguous representation. We eliminate repetition to improve clarity and reduce the chance of error.

*   **Thin Controllers:** Our controllers are lean and focused. They are the gatekeepers of our application, handling requests and responses, but delegating the heavy lifting of business logic to a dedicated service layer.

*   **Clean Code as a Standard:** We believe that code is read far more often than it is written. Therefore, we treat clarity as a primary feature.
    *   **No Remnants of Debugging:** Production code must be free of any debugging artifacts like `var_dump`, `dd`, or `console.log`.
    *   **No Commented-Out Code:** Dead code is noise. If it's not used, it's removed. Version control is our safety net, not commented-out blocks.
    *   **Self-Documenting Code:** We favor clear naming of variables, functions, and classes over explanatory comments. A comment should explain *why* something is done, not *what* it does.

*   **Code Correctness & Robustness:**
    *   **Functionality:** Code is not "done" until it works. Every commit pushed to our repository represents a stable and functional state of the application.
    *   **Graceful Error Handling:** We anticipate failure and design for it. Our users will never see an unhandled exception. We handle errors where they occur, with clarity and purpose.

## Coding in the Age of AI: A Paradigm Shift

We are at the forefront of a new era in software development, actively integrating AI assistants into our daily workflow. We see AI not as a replacement for human ingenuity, but as a powerful force multiplier—a partner that elevates our craft and accelerates our velocity.

### The Advantages We've Embraced:

*   **Accelerated Architecture & Design:** AI serves as an invaluable sparring partner in the conceptual phase. It helps us rapidly prototype architectural ideas, explore different design patterns, and make more informed decisions from the outset.
*   **Enhanced Cognitive Offloading:** By handling routine syntax lookups, boilerplate generation, and remembering API details, AI frees up our mental bandwidth. This allows us to stay in the flow and concentrate on higher-level problem-solving.
*   **Rapid Code Generation & Iteration:** AI empowers us to translate ideas into functional code at an unprecedented speed. It's a catalyst for writing new features, building tests, and refactoring existing code with greater efficiency.
*   **Streamlined Debugging:** When faced with a bug, AI can quickly suggest potential causes, analyze stack traces, and propose solutions, significantly reducing the time spent on diagnostics.

### Our Guiding Principles for AI Collaboration:

*   **The Engineer as the Pilot:** We are the pilots, and AI is our advanced co-pilot. We set the destination, make the critical decisions, and maintain ultimate control. The engineer is always the final authority.
*   **Critical Oversight is Non-Negotiable:** We rigorously review, test, and understand every piece of code suggested by AI. We are accountable for the quality and security of our work, regardless of its origin.
*   **Mastering the Art of the Prompt:** We recognize that "prompt engineering" is a crucial new skill. The clarity and context of our questions directly shape the quality of the answers. We are dedicated to mastering this dialogue.

By embracing AI, we are not just coding faster; we are coding smarter. We are augmenting our creativity and focusing our energy on what truly matters: building exceptional software.

## Our Technology Stack & Tools

We choose our tools pragmatically, selecting the best technology for the job at hand. We value both cutting-edge innovation and the stability of proven solutions.

### Backend (PHP)

*   **Frameworks:** We are polyglots within our own ecosystem, choosing the right tool for the right scale.
    *   **Symfony:** For large, complex, and feature-rich services that require a robust foundation.
    *   **Laravel:** For rapid application development where speed and convention are key.
    *   **Slim:** For lightweight microservices and APIs where performance and a minimal footprint are paramount.
*   **Asynchronous PHP:** We embrace the power of asynchronicity for high-performance, I/O-bound operations, using the **`react-php`** ecosystem to build scalable and responsive services.

### Frontend (JavaScript)

*   **Frameworks:** We build dynamic and engaging user experiences.
    *   **Vue.js:** Our preferred choice for building modern, interactive user interfaces.
    *   **React:** Leveraged in projects where its component model and ecosystem provide a distinct advantage.
*   **The Foundation:**
    *   **jQuery:** A trusted tool for targeted DOM manipulation, especially in legacy contexts.
    *   **Vanilla JS:** We are not afraid to use the raw power of the web platform for performance-critical code or when a framework is overkill.

## Our Development Process

*   **Coding Standards:**
    *   **PHP:** We adhere to the [Symfony Coding Standards](https://symfony.com/doc/current/contributing/code/standards.html). Code is automatically formatted using `php-cs-fixer`.
    *   **Shell Scripts:** We follow the [Google Shell Style Guide](https://google.github.io/styleguide/shell.xml) to ensure our scripts are robust and maintainable.

*   **Commit Message Format:** We use [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/). This creates a clean, understandable, and machine-readable history of our project's evolution.
    ```
    <type>[optional scope]: <description>
    ```
    *   **Allowed Types:** `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `chore`, `revert`.

*   **Architectural Style:**
    *   **Microservices:** Our system is a constellation of small, independent services, allowing for scalability, resilience, and independent deployment.
    *   **API-Driven Communication:** Services communicate through well-defined and versioned APIs (REST, gRPC).
    *   **Externalized Configuration:** We maintain a strict separation of code and configuration, following the principles of a twelve-factor app.
## Code Correctness & Robustness
- **Functionality:** Code is not "done" until it works
- **Testing Strategy:** Auto tests (unit/integration), manual QA for critical paths
- **Code Coverage:** Target 70%+ for services, 90%+ for security-critical code
---
## Performance & Scalability
- **Caching Strategy:** Implement where data is requested 2+ times
- **Database Queries:** N+1 problems caught in review
- **Async Operations:** Use for I/O-bound tasks (emails, webhooks, heavy processing)  

## Documentation
- **Self-documenting code:** Clear names > comments explaining WHAT
- **README files:** Every service/package has one (setup, API, examples)
- **API Documentation:** OpenAPI/Swagger for all endpoints
- **Decision Records (ADR):** Document WHY architectural choices were made    

## Development Workflow
- **Code Review:** Every change reviewed + approved before merge
- **Atomic Commits:** Each commit solves ONE problem (helps with bisect & revert)
- **No Merge Debt:** PRs merged within 24h or closed (avoid stale contexts)  

## Error Handling Philosophy
- **User-facing:** Never raw stack traces. User sees friendly message + error ID for support
- **Logging:** Full context logged + searchable (error ID maps to logs)
- **Observability:** Errors aggregated in monitoring (Sentry, DataDog, etc.)

Framework choice: We adapt to project requirements:
- **Frontend:** Vue.js (default), React (when ecosystem advantage outweighs overhead)
- **Backend:** Symfony (complex), Laravel (rapid), Slim (micro) — pick per scale

## Observability & Debugging
- **Logging Strategy:** Structured logs (JSON format), searchable by ID/context
- **Error Tracking:** Sentry/DataDog integration for production issues
- **Performance Monitoring:** Slow query detection, API latency tracking
- **Health Checks:** Services expose `/health` endpoint

## Team Collaboration
- **Knowledge Sharing:** New team member docs, runbooks for incidents
- **Code Review Culture:** Focus on learning, not blame
- **Post-Mortems:** Document incidents + lessons learned (no finger-pointing)

## Maintenance & Debt Management
- **Technical Debt:** Tracked, prioritized, addressed quarterly
- **Dependency Updates:** Security patches immediately, minor versions monthly
- **Deprecation Warnings:** 2-3 major versions before removal
*This document was created with the assistance of Gemini AI.* GitHub Copilot Kiro
