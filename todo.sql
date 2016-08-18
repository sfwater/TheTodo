CREATE TABLE todo (
  id int(11) NOT NULL,
  name varchar(255) NOT NULL,
  description varchar(255) NOT NULL,
  priority varchar(255) NOT NULL,
  due_date datetime NOT NULL,
  create_date datetime NOT NULL
);

ALTER TABLE todo ADD PRIMARY KEY (id);

ALTER TABLE todo MODIFY id int(11) NOT NULL AUTO_INCREMENT;