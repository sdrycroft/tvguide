TV Guide
========

```sql
CREATE TABLE programme (
  start BIGINT,
  start_bst TINYINT,
  stop BIGINT,
  stop_bst TINYINT,
  channel VARCHAR(64),
  title TEXT,
  description MEDIUMTEXT,
  url TEXT,
  category TEXT,
  rating VARCHAR(16),
  date VARCHAR(16),
  PRIMARY KEY(
    start, start_bst, channel)
) CHARSET=UTF8;
CREATE TABLE channel (
  num INT NOT NULL DEFAULT 1000,
  id VARCHAR(64),
  channel_description VARCHAR(255),
  img VARCHAR(255),
  PRIMARY KEY(
    id)
) CHARSET=UTF8;
```
