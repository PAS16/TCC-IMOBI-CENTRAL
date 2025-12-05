
CREATE SCHEMA IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8mb4;
USE `mydb`;

-- -----------------------------------------------------
-- USUARIO
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `USUARIO` (
    `idUSUARIO` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario` VARCHAR(50) NOT NULL UNIQUE,
    `senha` VARCHAR(255) NOT NULL,
    `tipo` ENUM('GESTOR','CORRETOR','admin') NOT NULL,
    `email` VARCHAR(100)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- GESTOR
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `GESTOR` (
    `idGESTOR` INT AUTO_INCREMENT PRIMARY KEY,
    `USUARIO_idUSUARIO` INT NOT NULL,
    `nome` VARCHAR(100) NOT NULL,
    FOREIGN KEY (`USUARIO_idUSUARIO`) REFERENCES `USUARIO`(`idUSUARIO`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- CORRETOR
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CORRETOR` (
    `idCORRETOR` INT AUTO_INCREMENT PRIMARY KEY,
    `USUARIO_idUSUARIO` INT NOT NULL,
    `nome` VARCHAR(100) NOT NULL,
    `creci` VARCHAR(20) NOT NULL UNIQUE,
    `telefone` VARCHAR(20),
    FOREIGN KEY (`USUARIO_idUSUARIO`) REFERENCES `USUARIO`(`idUSUARIO`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- CLIENTE
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `CLIENTE` (
    `idCLIENTE` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL,
    `cpf` CHAR(11) NOT NULL UNIQUE,
    `telefone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- PROPRIETARIO
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `PROPRIETARIO` (
    `idPROPRIETARIO` INT AUTO_INCREMENT PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL,
    `cpf` CHAR(11) NOT NULL UNIQUE,
    `telefone` VARCHAR(20),
    `email` VARCHAR(100)
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- IMOVEL
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `IMOVEL` (
    `idIMOVEL` INT AUTO_INCREMENT PRIMARY KEY,
    `PROPRIETARIO_idPROPRIETARIO` INT NOT NULL,
    `titulo` VARCHAR(100),
    `tipo` VARCHAR(50),
    `rua` VARCHAR(150),
    `numero` VARCHAR(10),
    `bairro` VARCHAR(100),
    `cidade` VARCHAR(100),
    `estado` VARCHAR(2),
    `valor` DECIMAL(10,2),
    `descricao` VARCHAR(300),
    `status` ENUM('Vendido','Disponivel') DEFAULT 'Disponivel',
    `qtd_quartos` INT,
    `qtd_banheiro` INT,
    `qtd_vagas` INT,
    `negociavel` ENUM('Sim','Não') DEFAULT 'Não',
    `financiavel` ENUM('Sim','Não') DEFAULT 'Não',
    FOREIGN KEY (`PROPRIETARIO_idPROPRIETARIO`) REFERENCES `PROPRIETARIO`(`idPROPRIETARIO`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- IMAGEM_IMOVEL
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `IMAGEM_IMOVEL` (
    `idIMAGEM` INT AUTO_INCREMENT PRIMARY KEY,
    `IMOVEL_idIMOVEL` INT NOT NULL,
    `caminho` VARCHAR(255) NOT NULL,
    `nome_original` VARCHAR(255) NOT NULL,
    FOREIGN KEY (`IMOVEL_idIMOVEL`) REFERENCES `IMOVEL`(`idIMOVEL`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- VISITA
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `VISITA` (
    `idVISITA` INT AUTO_INCREMENT,
    `CORRETOR_idCORRETOR` INT NOT NULL,
    `IMOVEL_idIMOVEL` INT NOT NULL,
    `CLIENTE_idCLIENTE` INT NOT NULL,
    `data_visita` DATE,
    `observacoes` TEXT,
    PRIMARY KEY (`idVISITA`, `CLIENTE_idCLIENTE`),
    FOREIGN KEY (`CORRETOR_idCORRETOR`) REFERENCES `CORRETOR`(`idCORRETOR`) ON DELETE CASCADE,
    FOREIGN KEY (`IMOVEL_idIMOVEL`) REFERENCES `IMOVEL`(`idIMOVEL`) ON DELETE CASCADE,
    FOREIGN KEY (`CLIENTE_idCLIENTE`) REFERENCES `CLIENTE`(`idCLIENTE`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- PROPOSTA
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `PROPOSTA` (
    `idPROPOSTA` INT AUTO_INCREMENT PRIMARY KEY,
    `VISITA_idVISITA` INT NOT NULL,
    `VISITA_CLIENTE_idCLIENTE` INT NOT NULL,
    `valor_ofertado` DECIMAL(10,2),
    `data_proposta` DATE,
    `status` VARCHAR(45),
    FOREIGN KEY (`VISITA_idVISITA`, `VISITA_CLIENTE_idCLIENTE`) 
        REFERENCES `VISITA`(`idVISITA`, `CLIENTE_idCLIENTE`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------
-- Inserindo USUÁRIOS
-- ---------------------------
INSERT INTO `USUARIO` (`usuario`, `senha`, `tipo`, `email`) VALUES
('admin1', 'senha123', 'admin', 'admin1@imobiliaria.com'),
('gestor1', 'senha123', 'GESTOR', 'gestor1@imobiliaria.com'),
('corretor1', 'senha123', 'CORRETOR', 'corretor1@imobiliaria.com'),
('corretor2', 'senha123', 'CORRETOR', 'corretor2@imobiliaria.com');

-- ---------------------------
-- Inserindo GESTORES
-- ---------------------------
INSERT INTO `GESTOR` (`USUARIO_idUSUARIO`, `nome`) VALUES
(2, 'Carlos Silva');

-- ---------------------------
-- Inserindo CORRETORES
-- ---------------------------
INSERT INTO `CORRETOR` (`USUARIO_idUSUARIO`, `nome`, `creci`, `telefone`) VALUES
(3, 'Ana Pereira', 'CRECI12345', '(11) 98765-4321'),
(4, 'Bruno Santos', 'CRECI67890', '(21) 99876-5432');

-- ---------------------------
-- Inserindo CLIENTES
-- ---------------------------
INSERT INTO `CLIENTE` (`nome`, `cpf`, `telefone`, `email`) VALUES
('João Almeida', '12345678901', '(11) 91234-5678', 'joao@gmail.com'),
('Mariana Costa', '10987654321', '(21) 92345-6789', 'mariana@gmail.com');

-- ---------------------------
-- Inserindo PROPRIETÁRIOS
-- ---------------------------
INSERT INTO `PROPRIETARIO` (`nome`, `cpf`, `telefone`, `email`) VALUES
('Pedro Oliveira', '11122233344', '(11) 93456-7890', 'pedro@imoveis.com'),
('Luiza Fernandes', '55566677788', '(21) 94567-8901', 'luiza@imoveis.com');

-- ---------------------------
-- Inserindo IMÓVEIS
-- ---------------------------
INSERT INTO `IMOVEL` (`PROPRIETARIO_idPROPRIETARIO`, `titulo`, `tipo`, `rua`, `numero`, `bairro`, `cidade`, `estado`, `valor`, `descricao`, `status`, `qtd_quartos`, `qtd_banheiro`, `qtd_vagas`, `negociavel`, `financiavel`) VALUES
(1, 'Apartamento Moderno', 'Apartamento', 'Rua das Flores', '123', 'Centro', 'Mongagua', 'SP', 350000.00, 'Apartamento bem localizado, próximo a comércio e transporte.', 'Disponivel', 2, 2, 1, 'Sim', 'Sim'),
(2, 'Casa Espaçosa', 'Casa', 'Av. Brasil', '456', 'Jardim América', 'Praia Grande', 'RJ', 750000.00, 'Casa com quintal grande e garagem para 2 carros.', 'Disponivel', 3, 3, 2, 'Não', 'Sim');

-- ---------------------------
-- Inserindo IMAGENS DE IMÓVEIS
-- ---------------------------

-- ---------------------------
-- Inserindo VISITAS
-- ---------------------------
INSERT INTO `VISITA` (`CORRETOR_idCORRETOR`, `IMOVEL_idIMOVEL`, `CLIENTE_idCLIENTE`, `data_visita`, `observacoes`) VALUES
(1, 1, 1, '2025-12-10', 'Cliente interessado em apartamento de 2 quartos.'),
(2, 2, 2, '2025-12-12', 'Cliente quer visitar casa com quintal.');

-- ---------------------------
-- Inserindo PROPOSTAS
-- ---------------------------
INSERT INTO `PROPOSTA` (`VISITA_idVISITA`, `VISITA_CLIENTE_idCLIENTE`, `valor_ofertado`, `data_proposta`, `status`) VALUES
(1, 1, 340000.00, '2025-12-11', 'Pendente'),
(2, 2, 740000.00, '2025-12-13', 'Aceita');
