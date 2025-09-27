# Une architecture dont vous êtes le héros

A comprehensive, multilingual guide for implementing Domain-Driven Design with API Platform, designed for developers of all levels.

## 🌍 Languages

- **Français** (French) - Primary language
- **English** - Full translation

## 🚀 Quick Start

### Prerequisites

- Hugo Extended 0.120.4 or later
- Git

### Local Development

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/hive-ddd-guide.git
   cd hive-ddd-guide
   ```

2. **Install Hugo**
   ```bash
   # On macOS with Homebrew
   brew install hugo
   
   # On Ubuntu/Debian
   sudo apt-get install hugo
   
   # On Windows with Chocolatey
   choco install hugo-extended
   ```

3. **Start the development server**
   ```bash
   hugo server -D
   ```

4. **Open your browser**
   Navigate to `http://localhost:1313`

### Building for Production

```bash
hugo --minify --gc
```

The built site will be in the `public/` directory.

## 📁 Project Structure

```
hugo-site/
├── content/                 # Content files
│   ├── _index.md           # Homepage (French)
│   ├── en/                 # English content
│   │   └── _index.md       # Homepage (English)
│   ├── getting-started/    # Getting started guides
│   ├── concepts/           # DDD concepts
│   ├── parcours/           # Learning paths
│   ├── implementation/     # Implementation guides
│   └── examples/           # Code examples
├── layouts/                # Hugo templates
│   ├── _default/
│   └── partials/
├── static/                 # Static assets
│   ├── css/
│   ├── js/
│   └── images/
├── config.yaml             # Hugo configuration
└── README.md
```

## 🎯 Target Audience

### 🟢 Beginners
- Computer science students
- Junior developers
- Career transition professionals
- **Prerequisites**: PHP and Symfony basics

### 🟡 Intermediate
- Developers with 2-5 years of experience
- **Prerequisites**: Symfony experience, DDD notions

### 🔴 Advanced
- Senior developers
- Software architects
- **Prerequisites**: DDD, CQRS, Event Sourcing mastery

## 📚 Content Structure

### Getting Started
- Basic DDD concepts
- Rich vs anemic models
- Event Storming workshops
- First implementation

### Learning Paths
- **"Choose your own adventure"** approach
- Decision points based on your context
- Progressive difficulty levels
- Time estimates for each path

### Implementation Guides
- API Platform setup
- Repository patterns
- Testing strategies
- Performance optimization

### Storage Types
- SQL (PostgreSQL, MySQL)
- NoSQL (MongoDB)
- External APIs
- ElasticSearch
- In-Memory storage
- Complex distributed storage

## 🛠️ Technologies Covered

- **API Platform** - REST/GraphQL API framework
- **Symfony** - PHP framework
- **Doctrine** - ORM and ODM
- **Event Sourcing** - Event storage patterns
- **CQRS** - Command Query Responsibility Segregation
- **Event Storming** - Collaborative design method
- **Temporal** - Workflow orchestration

## 🎨 Features

### Multilingual Support
- French (primary)
- English (full translation)
- Language switcher in navigation
- SEO-optimized for both languages

### Interactive Learning
- Progress tracking
- Decision trees
- Code examples with copy buttons
- Search functionality

### Responsive Design
- Mobile-first approach
- Accessible navigation
- Print-friendly styles
- Dark/light theme support

## 🚀 Deployment

### GitHub Pages
The site is automatically deployed to GitHub Pages on every push to the `main` branch.

### Custom Domain
1. Add your domain to the repository settings
2. Update the `cname` in `.github/workflows/deploy.yml`
3. Configure DNS records

### Other Platforms
- **Netlify**: Connect your GitHub repository
- **Vercel**: Import and deploy
- **AWS S3**: Upload the `public/` directory

## 🤝 Contributing

We welcome contributions! Here's how to get started:

### Reporting Issues
- Use GitHub Issues for bug reports
- Use GitHub Discussions for questions
- Provide clear reproduction steps

### Suggesting Changes
- Fork the repository
- Create a feature branch
- Make your changes
- Submit a pull request

### Content Guidelines
- Follow the existing structure
- Use clear, accessible language
- Include code examples
- Test on both languages

## 📖 Content Guidelines

### Writing Style
- **Clear and accessible**: Write for developers of all levels
- **Progressive**: Start simple, build complexity gradually
- **Practical**: Include real-world examples
- **Interactive**: Use decision points and choices

### Code Examples
- Use syntax highlighting
- Include copy buttons
- Provide context and explanations
- Test all examples

### Images and Diagrams
- Use Mermaid for diagrams
- Optimize images for web
- Include alt text for accessibility
- Use consistent styling

## 🔧 Development

### Adding New Content

1. **Create a new page**
   ```bash
   hugo new content/section/page-name.md
   ```

2. **Add to navigation**
   Update `config.yaml` with new menu items

3. **Translate content**
   Create corresponding files in `content/en/`

### Styling

- Edit `static/css/custom.css` for custom styles
- Use CSS custom properties for theming
- Follow mobile-first responsive design

### JavaScript

- Add functionality in `static/js/custom.js`
- Use vanilla JavaScript (no frameworks)
- Ensure accessibility compliance

## 📊 Analytics

The site includes built-in analytics support:

- Google Analytics (configurable)
- Search tracking
- Progress tracking
- User journey analysis

## 🛡️ Security

- No external dependencies in production
- Content Security Policy headers
- HTTPS enforcement
- Regular dependency updates

## 📈 Performance

- Optimized images
- Minified CSS/JS
- Gzip compression
- CDN-ready static assets

## 🎯 SEO

- Semantic HTML structure
- Meta tags for both languages
- Open Graph and Twitter Cards
- Structured data markup
- XML sitemaps

## 📞 Support

- **GitHub Issues**: Bug reports and feature requests
- **GitHub Discussions**: Questions and community support
- **Email**: Contact the maintainer directly

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- Based on the Hive project architecture
- Inspired by "API Platform Con 2025 - Et si on utilisait l'Event Storming ?" presentation
- Built with Hugo and the Learn theme
- Community contributions and feedback

---

**Ready to start your DDD journey?** Visit the [live site](https://yourusername.github.io/hive-ddd-guide) or [contribute to the project](https://github.com/yourusername/hive-ddd-guide)!
