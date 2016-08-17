CREATE TABLE trash (
  id int(11) NOT NULL,
  todo_name varchar(255) NOT NULL,
  todo_priority varchar(255) NOT NULL,
  todo_description varchar(255) NOT NULL,
  todo_due_date datetime NOT NULL,
  todo_create_date datetime NOT NULL
);

ALTER TABLE trash ADD PRIMARY KEY (id);

ALTER TABLE trash MODIFY id int(11) NOT NULL AUTO_INCREMENT;