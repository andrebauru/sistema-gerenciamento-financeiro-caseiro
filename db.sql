CREATE DATABASE terreiro_andretsc;
USE terreiro_andretsc;

CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(50) NOT NULL,
    valor VARCHAR(100) NOT NULL
);

INSERT INTO configuracoes (chave, valor) VALUES ('moeda', 'BRL'), ('timezone', 'America/Sao_Paulo');

CREATE TABLE gastos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data DATE NOT NULL,
    hora TIME NOT NULL
);

CREATE TABLE contas_a_pagar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data DATE NOT NULL,
    hora TIME NOT NULL,
    pago TINYINT(1) DEFAULT 0
);

CREATE TABLE metas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data DATE NOT NULL,
    hora TIME NOT NULL
);

CREATE TABLE investimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    descricao VARCHAR(255) NOT NULL,
    valor DECIMAL(10, 2) NOT NULL,
    data DATE NOT NULL,
    hora TIME NOT NULL
);

CREATE TABLE salario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    valor DECIMAL(10, 2) NOT NULL,
    data DATE NOT NULL
);
