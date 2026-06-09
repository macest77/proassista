# Docker setup for symfony

- PHP 8.2
- MySQL 8.3
- Nginx

### Next step is
- docker-compose up -d --build
- docker exec -it symfony-docker\_php\_1 bash
- cd ..
- symfony new symfony --version="6.4.*" --webapp

localhost:8001

#### Hint on Windows:
volumes in docker-compose.yml must be stored on main HDD 
    volumes:
      - C:\symfony:/var/www/symfony/



