ALTER TABLE Vehicle ADD COLUMN chassisNumber VARCHAR(191) AFTER licensePlate;
ALTER TABLE Vehicle ADD COLUMN engineCC VARCHAR(191) AFTER chassisNumber;
