![Epaphrodites Logo](https://github.com/epaphrodites/epaphrodites/blob/master/static/img/logo.png)

[![MIT License](https://img.shields.io/badge/License-MIT-green.svg)](https://choosealicense.com/licenses/mit/)

---

## 👋 About this package
The Epaphrodites Package Manager is a powerful configuration management tool designed to handle dynamic updates for your Epaphrodites projects. It provides seamless component installation, updates, and synchronization with automatic backup functionality to ensure your project remains stable during updates.

---

## ✨ Key Features

- 🔄 **Dynamic Updates**: Automatically sync your project with the latest components  
- 🛡️ **Backup System**: Automatic backup of existing files before updates  
- ⚙️ **Flexible Configuration**: YAML-based configuration for precise control  
- 📦 **Component Management**: Install, update, or replace specific components  
- 🔍 **Selective Updates**: Choose between full, specific, or new component updates  
- 📝 **Detailed Logging**: Comprehensive operation logs for tracking changes 

---

## 🚀 Quick Start

### Prerequisites

- PHP 8.0+
- Composer installed globally
- An existing **Epaphrodites** project

### Installation

Install the package via Composer:

```bash
composer require epaphrodites/packages
```

### Initialize the package configuration:

```bash
php vendor/epaphrodites/packages/src/AutoInstaller
```

- This command generates two essential files:

```synchrone-config.yaml``` - Configuration file for update preferences
```synchrone``` - command tool for package management

### 📖 Usage

Available Commands

- Installs or updates components based on your configuration
```bash
php synchrone -i
```
- Updates the package itself from Packagist

```bash
php synchrone -u
```

- Configuration File
The ``synchrone-config.yaml`` file allows you to control update behavior:

```bash
# Update Types Configuration
update_types:
  all: false          # Update all components
  specific: true      # Update only specified components
  new: false          # Update only new components

# Specific Component Configuration (when specific: true)
targets:
  bin:
    scripts: true
    utilities:
      helper: true
      validator: true
  public:
    layouts:
      header: true
      footer: false
    assets: false
```