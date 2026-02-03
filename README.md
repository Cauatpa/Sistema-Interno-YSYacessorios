# 📦 Sistema Interno YSY

Bem-vindo ao **Sistema Interno YSY** 🚀
Este repositório e um projeto com intuito de padronizar e melhorar os processos internos do e-commerce **YSY Acessórios**, focado em semijoias.

O objetivo principal é **reduzir trabalho manual**, **evitar erros operacionais** e **aumentar a produtividade da equipe**, utilizando soluções simples, escaláveis e bem documentadas.

---

## 📌 Visão Geral do Projeto

Este sistema pode conter **vários subprojetos**, como por exemplo:

* 📊 Automação de planilhas (Excel)
* 📦 Controle de estoque
* 🛒 Controle de entradas e saídas de produtos
* 📄 Geração de relatórios (PDF / Excel)
* 🔔 Monitoramento e alertas
* 🌐 Sistemas web internos (CRUDs)

Cada projeto possui seu próprio diretório, mas todos seguem um **padrão de organização**.

---

## 🗂 Estrutura do Repositório

```bash

---

## 🛠 Tecnologias Utilizadas

As tecnologias podem variar conforme o projeto, mas geralmente incluem:

### 💻 Linguagens

* **PHP** → sistemas web internos (CRUD)
* **SQL (MySQL / MariaDB)** → banco de dados
* **HTML / CSS / JavaScript** → interface web

### 📚 Bibliotecas e Ferramentas

* `openpyxl` → leitura/escrita de arquivos Excel
* `reportlab` → geração de PDFs
* `PDO` → conexão segura com banco de dados
* **Git & GitHub** → versionamento de código

---

## ⚙️ Pré-requisitos

* ✔️ Git
* ✔️ PHP 8+
* ✔️ MySQL ou MongoDB
* ✔️ Editor de código (VS Code recomendado)

---
## 🔧 Variáveis de Ambiente (Recomendado)

Para produção, utilize variáveis de ambiente para configurar credenciais e ambiente:

* `APP_ENV` → `production` (padrão), `development` ou `local`
* `APP_TIMEZONE` → exemplo: `America/Sao_Paulo`
* `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
* `INITIAL_ADMIN_USER`, `INITIAL_ADMIN_PASS`, `INITIAL_ADMIN_NAME` (cria admin inicial se não existir)

---

### 3️⃣ Ler o README do projeto

Cada projeto possui um **README próprio**, explicando:

* O que ele faz
* Como configurar
* Como executar

---

## 🧪 Ambiente de Testes

Os projetos podem ser executados:

* 🖥 Localmente (ambiente de testes)
* 🌐 Futuramente em servidor online

---

## 🧭 Roadmap (Planejamento Futuro)

* [X] Padronização de todos os projetos
* [X] Sistema web unificado
* [X] Controle de usuários e permissões
* [X] Logs automáticos
* [X] Dashboard administrativo
* [ ] Pagina de atendimento
* [ ] Dash Board para lotes
* [ ] Permissões por setor de autuação

---

## 👨‍💻 Autor

**Cauã Thurler**
Desenvolvedor Full-Stack Júnior | Automação de Processos | Sistemas Internos

Projeto desenvolvido para uso interno da **YSY Acessórios**.

---

✨ *Este README serve como base e pode ser adaptado conforme o crescimento do projeto.*
