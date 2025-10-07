<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

# 🧠 Smart Drive System

A secure and scalable cloud storage system inspired by **Google Drive**, built with **Laravel**.  
This application allows users to create personal folders, upload files, manage access permissions, and organize data efficiently using a **tree-based folder structure**.  
Each user has a private workspace ensuring full data isolation, with the ability to share specific folders and permissions with other users.

---

## 🚀 Features

- 📁 **Folder Management**
  - Create, delete, and move folders.
  - Validate destination folders before moving to prevent invalid nesting.
  - Maintain parent–child relationships between folders (tree-based structure).

- 📂 **File Management**
  - Upload and delete files within folders.
  - Support for multiple file types.
  - Files are organized and associated with specific folders.

- 🔐 **Permission Control**
  - Grant **read**, **edit**, or **delete** access to other users for any folder.
  - Shared access applies recursively to all subfolders and files.

- 🌲 **Tree-Based Folder Structure**
  - Implemented using parent-child relationships for efficient organization.
  - Recursive queries for nested folder retrieval.

- ⬇️ **Recursive Folder Download**
  - Download any folder as a `.zip` file, including all nested subfolders and files, preserving the hierarchy.

- 👥 **User Isolation**
  - Each user can only access their own folders and files unless explicit permission is granted.

---

## 🏗️ System Architecture

The system follows a **tree-based hierarchical model**, where each folder acts as a node.  
All operations (create, move, delete, download) are applied recursively based on the folder hierarchy.

### Core Entities
- **User:** Owns folders and files.  
- **Folder:** Represents a node in the tree.  
- **File:** Belongs to a folder.  
- **Permission:** Defines access levels (read/edit/delete) and shared users.

---

## ⚙️ Tech Stack

| Layer | Technology |
|-------|-------------|
| **Framework** | Laravel 11 |
| **Database** | MySQL |
| **Authentication** | Passport Auth (JWT) |
| **File Storage** | Laravel Storage (Local / Cloud) |
| **API Architecture** | RESTful APIs |


---
