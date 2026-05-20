-- Seed data with admin user and one sample module/unit/exercise
INSERT INTO users (username, password_hash, role, display_name) VALUES
('admin', '$2y$10$AlUbrmAy5.J5BUm0.KWHPujD4StNeTYmfckBAiJaxY//vgEchOita', 'admin', 'Administrator');

-- Additional seed accounts: one teacher and two students (password: "password")
INSERT INTO users (username, password_hash, role, display_name) VALUES
('teacher1', '$2y$10$AlUbrmAy5.J5BUm0.KWHPujD4StNeTYmfckBAiJaxY//vgEchOita', 'teacher', 'Mme. LeProf'),
('student1', '$2y$10$AlUbrmAy5.J5BUm0.KWHPujD4StNeTYmfckBAiJaxY//vgEchOita', 'student', 'Eleve Un'),
('student2', '$2y$10$AlUbrmAy5.J5BUm0.KWHPujD4StNeTYmfckBAiJaxY//vgEchOita', 'student', 'Eleve Deux');

INSERT INTO modules (title, description, `order`) VALUES
('Articles définis', 'Module sur les articles définis (le, la, les, l\')', 1);

INSERT INTO units (module_id, title, content_html, `order`) VALUES
(1, 'Unité 1 — Citer les articles', '<p>Exemples et explications sur les articles définis.</p>', 1);

-- Sample MCQ exercise stored as JSON (question + choices)
INSERT INTO exercises (unit_id, type, data, points, `order`) VALUES
(1, 'mcq', JSON_ARRAY(JSON_OBJECT('prompt','Choisissez l\'article défini correct pour: "____ chat est dans la cour"', 'choices', JSON_ARRAY(JSON_OBJECT('text','La','correct',0), JSON_OBJECT('text','L\'','correct',0), JSON_OBJECT('text','Les','correct',0), JSON_OBJECT('text','Le','correct',1)))), 1, 1);

-- Pretest exercises for module 1
INSERT INTO exercises (unit_id, type, data, points, `order`) VALUES
(1, 'pretest', JSON_ARRAY(JSON_OBJECT('prompt','Laquelle de ces phrases est correcte?','choices', JSON_ARRAY(JSON_OBJECT('text','J\'aime le oiseaux.','correct',0), JSON_OBJECT('text','J\'aime les oiseaux.','correct',1), JSON_OBJECT('text','J\'aime la oiseaux.','correct',0), JSON_OBJECT('text','J\'aime l\'oiseaux.','correct',0)))), 1, 2),
(1, 'pretest', JSON_ARRAY(JSON_OBJECT('prompt','Quel article défini convient pour le mot "école"?','choices', JSON_ARRAY(JSON_OBJECT('text','L\'','correct',1), JSON_OBJECT('text','la','correct',0), JSON_OBJECT('text','le','correct',0), JSON_OBJECT('text','les','correct',0)))), 1, 3);

-- Posttest exercises for module 1
INSERT INTO exercises (unit_id, type, data, points, `order`) VALUES
(1, 'posttest', JSON_ARRAY(JSON_OBJECT('prompt','Complète la phrase: "....amie de Houda arrive demain"','choices', JSON_ARRAY(JSON_OBJECT('text','Le','correct',0), JSON_OBJECT('text','la','correct',1), JSON_OBJECT('text','les','correct',0), JSON_OBJECT('text','l\'','correct',0)))), 1, 1);

-- Prerequisite (entry) questions
INSERT INTO exercises (unit_id, type, data, points, `order`) VALUES
(1, 'prereq', JSON_ARRAY(JSON_OBJECT('prompt','Quel mot est un verbe?','choices', JSON_ARRAY(JSON_OBJECT('text','chat','correct',0), JSON_OBJECT('text','sauter','correct',1), JSON_OBJECT('text','école','correct',0), JSON_OBJECT('text','manger','correct',1)))), 1, 1);

-- Enroll students into module 1
INSERT INTO user_courses (user_id, module_id) VALUES
((SELECT id FROM users WHERE username='student1'), 1),
((SELECT id FROM users WHERE username='student2'), 1);

-- Seed badges
INSERT INTO badges (code,title,description,icon) VALUES
('level_1','Découverte','A complété son premier niveau','/assets/icons/badge_level1.png'),
('level_2','Explorateur','Atteint le niveau 2','/assets/icons/badge_level2.png'),
('streak_7','7 jours de suite','7 jours de pratique consécutifs','/assets/icons/badge_streak7.png');
